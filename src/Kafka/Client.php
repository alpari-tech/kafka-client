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


namespace Alpari\Kafka;

use Alpari\Kafka;
use Alpari\Kafka\Common\Cluster;
use Alpari\Kafka\Common\Node;
use Alpari\Kafka\Consumer\Config as ConsumerConfig;
use Alpari\Kafka\Consumer\MemberAssignment;
use Alpari\Kafka\DTO\TopicPartitions;
use Alpari\Kafka\Error\AllBrokersNotAvailable;
use Alpari\Kafka\Error\KafkaException;
use Alpari\Kafka\Error\NetworkException;
use Alpari\Kafka\Error\TopicPartitionRequestException;
use Alpari\Kafka\Producer\Config as ProducerConfig;
use Alpari\Kafka\Record\FetchRequest;
use Alpari\Kafka\Record\FetchResponse;
use Alpari\Kafka\Record\GroupCoordinatorRequest;
use Alpari\Kafka\Record\GroupCoordinatorResponse;
use Alpari\Kafka\Record\HeartbeatRequest;
use Alpari\Kafka\Record\HeartbeatResponse;
use Alpari\Kafka\Record\JoinGroupRequest;
use Alpari\Kafka\Record\JoinGroupResponse;
use Alpari\Kafka\Record\LeaveGroupRequest;
use Alpari\Kafka\Record\LeaveGroupResponse;
use Alpari\Kafka\Record\OffsetCommitRequest;
use Alpari\Kafka\Record\OffsetCommitResponse;
use Alpari\Kafka\Record\OffsetFetchRequest;
use Alpari\Kafka\Record\OffsetFetchResponse;
use Alpari\Kafka\Record\OffsetsRequest;
use Alpari\Kafka\Record\OffsetsResponse;
use Alpari\Kafka\Record\ProduceRequest;
use Alpari\Kafka\Record\ProduceResponse;
use Alpari\Kafka\Record\SyncGroupRequest;
use Alpari\Kafka\Record\SyncGroupResponse;
use Alpari\Kafka\Stream\SocketStream;

/**
 * Kafka low-level client
 */
class Client
{
    /**
     * Cluster configuration
     *
     * @var Cluster
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
     * Produce messages to the specific topic partition
     *
     * @param array $topicPartitionMessages List of messages for each topic and partition
     * @throws TopicPartitionRequestException If produce was completed partially on specific partitions
     *
     * @return Kafka\DTO\ProduceResponsePartition[][]
     */
    public function produce(array $topicPartitionMessages): array
    {
        $errors = [];
        $result = $this->clusterRequest($topicPartitionMessages, function (array $nodeTopicPartitionMessages) {
            $request = new ProduceRequest(
                $nodeTopicPartitionMessages,
                $this->configuration[ProducerConfig::ACKS],
                $this->configuration[ProducerConfig::TRANSACTIONAL_ID],
                $this->configuration[ProducerConfig::TIMEOUT_MS],
                $this->configuration[ProducerConfig::CLIENT_ID]
            );

            return $request;
        }, ProduceResponse::class, function (array $result, ProduceResponse $response) use (&$errors) {
            /** @var Kafka\DTO\ProduceResponsePartition[] $partitions */
            foreach ($response->topics as $topic => $produceResponseTopic) {
                foreach ($produceResponseTopic->partitions as $partitionId => $partitionInfo) {
                    $isSucceeded = $partitionInfo->errorCode === 0;
                    if ($isSucceeded) {
                        $result[$topic][$partitionId] = $partitionInfo;
                    } else {
                        $error = KafkaException::fromCode(
                            $partitionInfo->errorCode,
                            compact('topic', 'partitionId')
                        );

                        $errors[$topic][$partitionId] = $error;
                    }
                }
            }
            return $result;
        });

        if (!empty($errors)) {
            throw new TopicPartitionRequestException($result, $errors);
        }

        return $result;
    }

    /**
     * Commits the offsets for topic partitions for the concrete consumer group
     *
     * @param Node    $coordinatorNode       Current group coordinator for $groupId
     * @param string  $groupId               Name of the group
     * @param string  $memberId              Name of the group member
     * @param integer $generationId          Current generation of consumer
     * @param array   $topicPartitionOffsets List of topic => partitions for fetching information
     * @param integer $retentionTimeMs       Retention time for this offset, -1 = use broker time
     *
     * @throws Kafka\Error\OffsetMetadataTooLarge
     * @throws Kafka\Error\GroupLoadInProgress
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\IllegalGeneration
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\RebalanceInProgress
     * @throws Kafka\Error\InvalidCommitOffsetSize
     * @throws Kafka\Error\TopicAuthorizationFailed
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function commitGroupOffsets(
        Node $coordinatorNode,
        string $groupId,
        string $memberId,
        int $generationId,
        array $topicPartitionOffsets,
        int $retentionTimeMs
    ): void {
        $stream  = $coordinatorNode->getConnection($this->configuration);
        $request = new OffsetCommitRequest(
            $groupId,
            $generationId,
            $memberId,
            $retentionTimeMs,
            $topicPartitionOffsets,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = OffsetCommitResponse::unpack($stream);
        foreach ($response->topics as $topic => $offsetCommitResponseTopic) {
            foreach ($offsetCommitResponseTopic->partitions as $partitionId => $partition) {
                if ($partition->errorCode !== 0) {
                    throw KafkaException::fromCode($partition->errorCode, compact('topic', 'partitionId'));
                }
            }
        }
    }

    /**
     * Fetches the offsets for topic partition for the concrete consumer group
     *
     * @param Node              $coordinatorNode  Current group coordinator for $groupId
     * @param string            $groupId          Name of the group
     * @param TopicPartitions[] $topicPartitions  List of topic => partitions for fetching information or null
     *
     * @return array
     *
     * Exception UnknownTopicOrPartition is ignored and silenced, offset -1 will be returned
     *
     * @throws Kafka\Error\GroupLoadInProgress
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\IllegalGeneration
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\TopicAuthorizationFailed
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function fetchGroupOffsets(Node $coordinatorNode, string $groupId, ?array $topicPartitions = null): array
    {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new OffsetFetchRequest(
            $groupId,
            $topicPartitions,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = OffsetFetchResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            throw KafkaException::fromCode($response->errorCode, compact('groupId'));
        }
        $result = [];
        foreach ($response->topics as $topic => $offsetFetchResponseTopic) {
            foreach ($offsetFetchResponseTopic->partitions as $partitionId => $partition) {
                $isUnknownTopicPartition = $partition->errorCode === KafkaException::UNKNOWN_TOPIC_OR_PARTITION;
                if ($partition->errorCode !== 0 && !$isUnknownTopicPartition) {
                    throw KafkaException::fromCode($partition->errorCode, compact('topic', 'partitionId'));
                }
                $result[$topic][$partitionId] = $partition->offset;
            }
        }

        return $result;
    }

    /**
     * Joins the group with specified protocol and member information
     *
     * @param Node   $coordinatorNode Current group coordinator for $groupId
     * @param string $groupId         Name of the group
     * @param string $memberId        Name of the group member
     * @param string $protocolType    Type of protocol to use for joining
     * @param array  $groupProtocols  Configuration of group protocols
     *
     * @return JoinGroupResponse
     *
     * @throws Kafka\Error\GroupLoadInProgress
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\InconsistentGroupProtocol
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\InvalidSessionTimeout
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function joinGroup(
        Node $coordinatorNode,
        string $groupId,
        string $memberId,
        string $protocolType,
        array $groupProtocols
    ): JoinGroupResponse {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new JoinGroupRequest(
            $groupId,
            $this->configuration[ConsumerConfig::SESSION_TIMEOUT_MS],
            $this->configuration[ConsumerConfig::REBALANCE_TIMEOUT_MS],
            $memberId,
            $protocolType,
            $groupProtocols,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = JoinGroupResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('coordinatorNode', 'groupId', 'memberId', 'protocolType');
            throw KafkaException::fromCode($response->errorCode, $context);
        }

        return $response;
    }

    /**
     * Removes the group member from the current group
     *
     * @param Node   $coordinatorNode Current group coordinator for $groupId
     * @param string $groupId         Name of the group
     * @param string $memberId        Name of the group member
     *
     * @throws Kafka\Error\GroupLoadInProgress
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function leaveGroup(Node $coordinatorNode, string $groupId, string $memberId): void
    {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new LeaveGroupRequest(
            $groupId,
            $memberId,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = LeaveGroupResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('coordinatorNode', 'groupId', 'memberId');
            throw KafkaException::fromCode($response->errorCode, $context);
        }
    }

    /**
     * Synchronizes group member with the group
     *
     * @param Node               $coordinatorNode  Current group coordinator for $groupId
     * @param string             $groupId          Name of the group
     * @param string             $memberId         Name of the group member
     * @param integer            $generationId     Current generation of consumer
     * @param MemberAssignment[] $groupAssignments Group assignments
     *
     * @return SyncGroupResponse
     *
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\IllegalGeneration
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\RebalanceInProgress
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function syncGroup(
        Node $coordinatorNode,
        string $groupId,
        string $memberId,
        int $generationId,
        array $groupAssignments = []
    ): SyncGroupResponse {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new SyncGroupRequest(
            $groupId,
            $generationId,
            $memberId,
            $groupAssignments,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = SyncGroupResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('coordinatorNode', 'groupId', 'memberId', 'generationId', 'groupAssignments');
            throw KafkaException::fromCode($response->errorCode, $context);
        }

        return $response;
    }

    /**
     * Performs a heartbeat request for the current group
     *
     * @param Node    $coordinatorNode Current group coordinator for $groupId
     * @param string  $groupId Name of the group
     * @param string  $memberId Name of the group member
     * @param integer $generationId Current group generation
     *
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\NotCoordinatorForGroup
     * @throws Kafka\Error\IllegalGeneration
     * @throws Kafka\Error\UnknownMemberId
     * @throws Kafka\Error\RebalanceInProgress
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function heartbeat(Node $coordinatorNode, string $groupId, string $memberId, int $generationId): void
    {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new HeartbeatRequest(
            $groupId,
            $generationId,
            $memberId,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = HeartbeatResponse::unpack($stream);
        if ($response->errorCode !== 0) {
            $context = compact('coordinatorNode', 'groupId', 'memberId', 'generationId');
            throw KafkaException::fromCode($response->errorCode, $context);
        }
    }

    /**
     * Discovers the group coordinator node for the group
     *
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function getGroupCoordinator(string $groupId): Node
    {
        $clusterNodes = $this->cluster->nodes();
        $failures     = [];
        foreach ($clusterNodes as $node) {
            $stream = $node->getConnection($this->configuration);

            try {
                $request = new GroupCoordinatorRequest(
                    $groupId,
                    $this->configuration[ConsumerConfig::CLIENT_ID]
                );
                $request->writeTo($stream);
                $response = GroupCoordinatorResponse::unpack($stream);
                if ($response->errorCode !== 0) {
                    throw KafkaException::fromCode($response->errorCode, compact('groupId'));
                }

                $coordinator = $this->cluster->nodeById($response->coordinator->nodeId);

                return $coordinator;
            } catch (NetworkException $e) {
                $failures[] = $e;
                continue;
            }
        }

        throw new AllBrokersNotAvailable($failures, KafkaException::BROKER_NOT_AVAILABLE);
    }

    /**
     * Fetches messages from the specified topic and partitions
     *
     * @param array   $topicPartitionOffsets List of topic partition offsets as start point for fetching
     * @param integer $timeout               Timeout in ms to wait for fetching
     *
     * @return Kafka\DTO\RecordBatch[][]
     *
     * @throws Kafka\Error\OffsetOutOfRange
     * @throws Kafka\Error\UnknownTopicOrPartition
     * @throws Kafka\Error\NotLeaderForPartition
     * @throws Kafka\Error\ReplicaNotAvailable
     * @throws Kafka\Error\UnknownError
     */
    public function fetch(array $topicPartitionOffsets, int $timeout): array
    {
        $timeout = min($this->configuration[ConsumerConfig::FETCH_MAX_WAIT_MS], $timeout);
        $errors  = [];

        $result = $this->clusterRequest($topicPartitionOffsets, function (array $nodeTopicRequest) use ($timeout) {
            $request = new FetchRequest(
                $nodeTopicRequest,
                $timeout,
                $this->configuration[ConsumerConfig::FETCH_MIN_BYTES],
                $this->configuration[ConsumerConfig::MAX_PARTITION_FETCH_BYTES],
                $this->configuration[ConsumerConfig::ISOLATION_LEVEL],
                -1,
                $this->configuration[ConsumerConfig::CLIENT_ID]
            );

            return $request;
        }, FetchResponse::class, function (array $result, FetchResponse $response) use (&$errors) {
            foreach ($response->topics as $topic => $fetchResponseTopic) {
                foreach ($fetchResponseTopic->partitions as $partitionId => $responsePartition) {
                    $isSucceeded = $responsePartition->errorCode === 0;
                    if ($isSucceeded) {
                        $result[$topic][$partitionId] = $responsePartition->getRecordBatches();
                    } else {
                        $error = KafkaException::fromCode(
                            $responsePartition->errorCode,
                            compact('topic', 'partitionId')
                        );

                        $errors[$topic][$partitionId] = $error;
                    }
                }
            }

            return $result;
        }, $timeout);

        if (!empty($errors)) {
            throw new TopicPartitionRequestException($result, $errors);
        }

        return $result;
    }

    /**
     * Requests all offsets for the list of topic partitions
     *
     * This query will be made over the current cluster by checking the metadata for each topic partition
     * @param array $topicPartitions List of topic partitions
     *
     * @return array Array in the form: [topic => [partition => offset]]
     *
     * @throws Kafka\Error\UnknownTopicOrPartition
     * @throws Kafka\Error\NotLeaderForPartition
     * @throws Kafka\Error\UnknownError
     */
    public function fetchTopicPartitionOffsets(array $topicPartitions): array
    {
        $result = $this->clusterRequest($topicPartitions, function (array $nodeTopicRequest) {
            $request = new OffsetsRequest(
                $nodeTopicRequest,
                -1,
                $this->configuration[ConsumerConfig::CLIENT_ID]
            );

            return $request;
        }, OffsetsResponse::class, function (array $result, OffsetsResponse $response) {
            foreach ($response->topics as $topic => $offsetsResponsePartitions) {
                foreach ($offsetsResponsePartitions->partitions as $partitionId => $partitionMetadata) {
                    if ($partitionMetadata->errorCode !== 0) {
                        throw KafkaException::fromCode($partitionMetadata->errorCode, compact('topic', 'partitionId'));
                    }
                    $result[$topic][$partitionId] = $partitionMetadata->offset;
                }
            }

            return $result;
        });

        return $result;
    }

    private function clusterRequest(
        array $topicPartitionsRequest,
        \Closure $nodeRequest,
        string $responseClass,
        \Closure $responseAggregator,
        ?int $timeout = null
    ) {
        $requestByNode = [];
        $exceptions    = [];

        foreach ($topicPartitionsRequest as $topic => $partitions) {
            foreach ($partitions as $partitionId => $partitionData) {
                try {
                    $leaderNode = $this->cluster->leaderFor($topic, $partitionId);
                    $requestByNode[$leaderNode->nodeId][$topic][$partitionId] = $partitionData;
                } catch (\Exception $exception) {
                    $exceptions[$topic][$partitionId] = $exception;
                }
            }
        }

        // TODO: Implement StreamGroup(Stream[] $connections) and Stream->joinGroup(StreamGroup $group)
        $socketAccessor = function (SocketStream $socket) {
            if (!$socket->isConnected()) {
                $socket->connect();
            }

            return $socket->streamSocket;
        };
        $socketAccessor  = $socketAccessor->bindTo(null, SocketStream::class);
        $readNodeSockets = [];

        foreach ($requestByNode as $nodeId => $nodeTopicPartitions)
        {
            try {
                /** @var AbstractRecord $request */
                $request = $nodeRequest($nodeTopicPartitions);
                $stream  = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);

                $readNodeSockets[$nodeId] = $socketAccessor($stream);
                $request->writeTo($stream);
            } catch (\Exception $exception) {
                foreach ($nodeTopicPartitions as $topic => $partitions) {
                    foreach (array_keys($partitions) as $partitionId) {
                        $exceptions[$topic][$partitionId] = $exception;
                    }
                }
            }
        }

        $incompleteReads = $readNodeSockets;
        if (!isset($timeout)) {
            $timeout = $this->configuration[ConsumerConfig::REQUEST_TIMEOUT_MS];
        }

        $responses       = [];
        $finishTime      = microtime(true) + 2 * ($timeout / 1000);
        $canWaitMoreTime = true;
        while (!empty($incompleteReads) && $canWaitMoreTime) {
            $readSelect  = $incompleteReads;
            $writeSelect = $exceptSelect = null;
            if (stream_select($readSelect, $writeSelect, $exceptSelect, intdiv($timeout, 1000), $timeout % 1000) > 0) {
                foreach ($readSelect as $resourceToRead) {
                    $nodeId = array_search($resourceToRead, $readNodeSockets, true);
                    try {
                        $connection         = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);
                        $responses[$nodeId] = $responseClass::unpack($connection);
                    } catch (\Exception $exception) {
                        foreach ($requestByNode[$nodeId] as $topic => $partitions) {
                            foreach (array_keys($partitions) as $partitionId) {
                                $exceptions[$topic][$partitionId] = $exception;
                            }
                        }
                    }
                }
                $incompleteReads = array_diff($incompleteReads, $readSelect);
            }
            $canWaitMoreTime = microtime(true) < $finishTime;
        }

        $result = array_reduce($responses, $responseAggregator, []);

        if (!empty($exceptions)) {
            throw new TopicPartitionRequestException($result, $exceptions);
        }

        return $result;
    }
}
