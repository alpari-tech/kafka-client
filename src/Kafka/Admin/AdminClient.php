<?php
/*
 * This file is part of the Alpari Kafka client.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);


namespace Alpari\Kafka\Admin;

use Alpari\Kafka\AbstractRecord;
use Alpari\Kafka\Common\Cluster;
use Alpari\Kafka\Common\Config;
use Alpari\Kafka\Common\Node;
use Alpari\Kafka\DTO\DescribeGroupResponseMetadata;
use Alpari\Kafka\DTO\ListGroupResponseProtocol;
use Alpari\Kafka\DTO\OffsetFetchResponseTopic;
use Alpari\Kafka\Error\InvalidGroupId;
use Alpari\Kafka\Error\KafkaException;
use Alpari\Kafka\Error\NotCoordinatorForGroup;
use Alpari\Kafka\Error\RequestTimedOut;
use Alpari\Kafka\Record\AbstractRequest;
use Alpari\Kafka\Record\ApiVersionsRequest;
use Alpari\Kafka\Record\ApiVersionsResponse;
use Alpari\Kafka\Record\DescribeGroupsRequest;
use Alpari\Kafka\Record\DescribeGroupsResponse;
use Alpari\Kafka\Record\GroupCoordinatorRequest;
use Alpari\Kafka\Record\GroupCoordinatorResponse;
use Alpari\Kafka\Record\ListGroupsRequest;
use Alpari\Kafka\Record\ListGroupsResponse;
use Alpari\Kafka\Record\MetadataRequest;
use Alpari\Kafka\Record\MetadataResponse;
use Alpari\Kafka\Record\OffsetFetchRequest;
use Alpari\Kafka\Record\OffsetFetchResponse;

/**
 * Kafka low-level administrative client
 */
class AdminClient
{
    /**
     * Cluster configuration
     */
    private $cluster;

    /**
     * Client configuration
     */
    private $configuration;

    public function __construct(Cluster $cluster, array $configuration = [])
    {
        $this->cluster       = $cluster;
        $this->configuration = $configuration;
    }

    /**
     * Describes group of consumers by name
     */
    public function describeGroup(string $groupId): DescribeGroupResponseMetadata
    {
        $coordinator = $this->findCoordinator($groupId);
        $request     = new DescribeGroupsRequest([$groupId], $this->configuration[Config::CLIENT_ID]);
        $stream      = $coordinator->getConnection($this->configuration);
        $request->writeTo($stream);
        $response = DescribeGroupsResponse::unpack($stream);
        $metadata = $response->groups[$groupId] ?? null;

        if ($metadata === null) {
            throw new InvalidGroupId([
                'error' => "Response from broker contained no metadata for group ${groupId}"
            ]);
        }

        return $metadata;
    }

    /**
     * Performs an API Versions request on given cluster node
     */
    public function getApiVersions(Node $node): array
    {
        $stream  = $node->getConnection($this->configuration);
        $request = new ApiVersionsRequest($this->configuration[Config::CLIENT_ID]);
        $request->writeTo($stream);
        $response = ApiVersionsResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('node');
            throw KafkaException::fromCode($response->errorCode, $context);
        }

        return $response->apiVersions;
    }

    /**
     * Returns all broker nodes
     *
     * @return Node[]
     */
    public function findAllBrokers(): array
    {
        $request  = new MetadataRequest();
        /** @var MetadataResponse $response */
        $response = $this->sendAnyNode($request, MetadataResponse::class);

        return $response->brokers;
    }


    /**
     * Lists all available groups
     *
     * @return array|ListGroupResponseProtocol[]
     */
    public function listAllGroups(): array
    {
        $result = [];
        foreach ($this->findAllBrokers() as $brokerNode) {
            try {
                $groups = $this->listGroups($brokerNode);
            } catch (\Exception $e) {
                trigger_error("Failed to find groups from broker {$brokerNode->nodeId}", E_USER_NOTICE);
                $groups = [];
            } finally {
                $result[$brokerNode->nodeId] = $groups;
            }
        }

        return $result;
    }

    /**
     * Finds a coordinator for the group
     *
     * @param string $groupId   Name of the group
     * @param int    $timeoutMs Timeout for looking coordinator
     *
     * @throws RequestTimedOut If command was timed out
     *
     * @return Node
     */
    public function findCoordinator(string $groupId, int $timeoutMs = 0): Node
    {
        $request = new GroupCoordinatorRequest($groupId, $this->configuration[Config::CLIENT_ID]);

        $startTime = microtime(true);
        do {
            try {
                /** @var GroupCoordinatorResponse $response */
                $response = $this->sendAnyNode($request, GroupCoordinatorResponse::class);
            } catch (\Exception $internalException) {
                $response = null;
            }
            $isNegativeResponse = $response === null
                || $response->errorCode === KafkaException::GROUP_COORDINATOR_NOT_AVAILABLE;

            if ($isNegativeResponse) {
                usleep($this->configuration[Config::RETRY_BACKOFF_MS]);
            }
            $canWaitMoreTime = microtime(true) - $startTime < $timeoutMs;
        } while ($isNegativeResponse && $canWaitMoreTime);

        if ($isNegativeResponse ) {
            throw new RequestTimedOut([
                'error' => 'The consumer group command timed out while waiting for group to initialize'
            ], $internalException ?? null);
        }

        $node = $this->cluster->nodeById($response->coordinator->nodeId);
        if ($node === null) {
            throw new NotCoordinatorForGroup([
                'error' => "No coordinator for the group {$groupId}"
            ]);
        }

        return $node;
    }

    /**
     * Lists group available on node
     *
     * @param Node $node
     *
     * @throws KafkaException
     *
     * @return ListGroupResponseProtocol[]
     */
    public function listGroups(Node $node): array
    {
        $stream  = $node->getConnection($this->configuration);
        $request = new ListGroupsRequest($this->configuration[Config::CLIENT_ID]);
        $request->writeTo($stream);
        $response = ListGroupsResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('node');
            throw KafkaException::fromCode($response->errorCode, $context);
        }

        return $response->groups;
    }

    /**
     * List all group topic partition offsets for specified groupID
     *
     * @param string $groupId Identifier of group
     *
     * @return OffsetFetchResponseTopic[]
     */
    public function listGroupOffsets(string $groupId): array
    {
        $coordinator = $this->findCoordinator($groupId);
        $request     = new OffsetFetchRequest($groupId, null, $this->configuration[Config::CLIENT_ID]);
        $stream      = $coordinator->getConnection($this->configuration);
        $request->writeTo($stream);
        $response = OffsetFetchResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('groupId');
            throw KafkaException::fromCode($response->errorCode, $context);
        }

        return $response->topics;
    }

    /**
     * Sends request to any existing node
     *
     * @param AbstractRequest $request       Instance of request to send
     * @param string          $responseClass Response class name to unpack
     *
     * @return AbstractRecord
     * @throws \RuntimeException
     */
    private function sendAnyNode(AbstractRequest $request, string $responseClass): AbstractRecord
    {
        foreach ($this->cluster->nodes() as $node) {
            try {
                $stream = $node->getConnection($this->configuration);
                $request->writeTo($stream);

                return $responseClass::unpack($stream);
            } catch (\Exception $e) {
                trigger_error($e->getMessage(), E_USER_NOTICE);
            }
        }
        $requestClass = get_class($request);
        throw new \RuntimeException("Request {$requestClass} failed on brokers");
    }
}
