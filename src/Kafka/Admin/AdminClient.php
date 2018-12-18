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


namespace Protocol\Kafka\Admin;

use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\Config;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\DTO\ApiVersionsResponseMetadata;
use Protocol\Kafka\DTO\DescribeGroupResponseMetadata;
use Protocol\Kafka\DTO\OffsetFetchResponsePartition;
use Protocol\Kafka\DTO\OffsetFetchResponseTopic;
use Protocol\Kafka\Error\InvalidGroupId;
use Protocol\Kafka\Error\KafkaException;
use Protocol\Kafka\Error\RequestTimedOut;
use Protocol\Kafka\AbstractRecord;
use Protocol\Kafka\Record\AbstractRequest;
use Protocol\Kafka\Record\ApiVersionsRequest;
use Protocol\Kafka\Record\ApiVersionsResponse;
use Protocol\Kafka\Record\DescribeGroupsRequest;
use Protocol\Kafka\Record\DescribeGroupsResponse;
use Protocol\Kafka\Record\GroupCoordinatorRequest;
use Protocol\Kafka\Record\GroupCoordinatorResponse;
use Protocol\Kafka\Record\ListGroupsRequest;
use Protocol\Kafka\Record\ListGroupsResponse;
use Protocol\Kafka\Record\MetadataRequest;
use Protocol\Kafka\Record\MetadataResponse;
use Protocol\Kafka\Record\OffsetFetchRequest;
use Protocol\Kafka\Record\OffsetFetchResponse;

/**
 * Kafka low-level administrative client
 */
class AdminClient
{
    /**
     * Cluster configuration
     *
     * @var Cluster
     */
    private $cluster;

    /**
     * Client configuration
     *
     * @var array
     */
    private $configuration;

    public function __construct(Cluster $cluster, array $configuration = [])
    {
        $this->cluster       = $cluster;
        $this->configuration = $configuration;
    }

    /**
     * Describes group of consumers by name
     *
     * @param string $groupId Identifier of group
     *
     * @return DescribeGroupResponseMetadata
     */
    public function describeGroup($groupId)
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
     * Performs an API Versions request
     *
     * @param Node $node Node for querying versions
     *
     * @return ApiVersionsResponseMetadata[]
     */
    public function getApiVersions(Node $node)
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
     * @return array|Node[]
     */
    public function findAllBrokers()
    {
        $request  = new MetadataRequest();
        /** @var MetadataResponse $response */
        $response = $this->sendAnyNode($request, MetadataResponse::class);

        return $response->brokers;
    }


    /**
     * @return array|
     */
    public function listAllGroups()
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
     * @return null|Node
     * @throws RequestTimedOut
     */
    public function findCoordinator($groupId, $timeoutMs = 0)
    {
        $request = new GroupCoordinatorRequest($groupId, $this->configuration[Config::CLIENT_ID]);

        $startTime = microtime(true);
        do {
            try {
                /** @var GroupCoordinatorResponse $response */
                $response = $this->sendAnyNode($request, GroupCoordinatorResponse::class);
            } catch (\Exception $e) {
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
            ], $e ?? null);
        }

        return $this->cluster->nodeById($response->coordinator->nodeId);
    }

    /**
     * Lists group available on node
     *
     * @param Node $node
     *
     * @return array
     * @throws KafkaException
     */
    public function listGroups(Node $node)
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
    public function listGroupOffsets($groupId)
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
    private function sendAnyNode(AbstractRequest $request, $responseClass)
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
