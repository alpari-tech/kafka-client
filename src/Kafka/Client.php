<?php
/**
 * @author Alexander.Lisachenko
 * @date 02.08.2016
 */

namespace Protocol\Kafka;

use Protocol\Kafka;
use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\Consumer\Config as ConsumerConfig;
use Protocol\Kafka\Error\KafkaException;
use Protocol\Kafka\Producer\Config as ProducerConfig;
use Protocol\Kafka\Record\FetchRequest;
use Protocol\Kafka\Record\FetchResponse;
use Protocol\Kafka\Record\GroupCoordinatorRequest;
use Protocol\Kafka\Record\GroupCoordinatorResponse;
use Protocol\Kafka\Record\HeartbeatRequest;
use Protocol\Kafka\Record\HeartbeatResponse;
use Protocol\Kafka\Record\JoinGroupRequest;
use Protocol\Kafka\Record\JoinGroupResponse;
use Protocol\Kafka\Record\LeaveGroupRequest;
use Protocol\Kafka\Record\LeaveGroupResponse;
use Protocol\Kafka\Record\OffsetCommitRequest;
use Protocol\Kafka\Record\OffsetCommitResponse;
use Protocol\Kafka\Record\OffsetFetchRequest;
use Protocol\Kafka\Record\OffsetFetchResponse;
use Protocol\Kafka\Record\OffsetsRequest;
use Protocol\Kafka\Record\OffsetsResponse;
use Protocol\Kafka\Record\ProduceRequest;
use Protocol\Kafka\Record\ProduceResponse;
use Protocol\Kafka\Record\SyncGroupRequest;
use Protocol\Kafka\Record\SyncGroupResponse;
use Protocol\Kafka\Stream\SocketStream;

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
     * Produce messages to the specific topic partition
     *
     * @param array $topicPartitionMessages List of messages for each topic and partition
     *
     * @return ProduceResponse
     */
    public function produce(array $topicPartitionMessages)
    {
        $result = $this->clusterRequest($topicPartitionMessages, function (array $nodeTopicPartitionMessages) {
            $request = new ProduceRequest(
                $nodeTopicPartitionMessages,
                $this->configuration[ProducerConfig::ACKS],
                $this->configuration[ProducerConfig::TIMEOUT_MS],
                $this->configuration[ProducerConfig::CLIENT_ID]
            );

            return $request;
        }, ProduceResponse::class, function (array $result, ProduceResponse $response) {
            /** @var Kafka\DTO\ProduceResponsePartition[] $partitions */
            foreach ($response->topics as $topic => $partitions) {
                foreach ($partitions as $partitionId => $partitionInfo) {
                    if ($partitionInfo->errorCode !== 0) {
                        throw KafkaException::fromCode($partitionInfo->errorCode, compact('topic', 'partitionId'));
                    }
                    $result[$topic][$partitionId] = $partitionInfo;
                }
            }
            return $result;
        });

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
        $groupId,
        $memberId,
        $generationId,
        array $topicPartitionOffsets
    )
    {
        $stream  = $coordinatorNode->getConnection($this->configuration);
        $request = new OffsetCommitRequest(
            $groupId,
            $generationId,
            $memberId,
            $topicPartitionOffsets,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = OffsetCommitResponse::unpack($stream);
        foreach ($response->topics as $topic => $partitions) {
            foreach ($partitions as $partitionId => $errorCode) {
                if ($errorCode !== 0) {
                    throw KafkaException::fromCode($errorCode, compact('topic', 'partitionId'));
                }
            }
        }
    }

    /**
     * Fetches the offsets for topic partition for the concrete consumer group
     *
     * @param Node   $coordinatorNode Current group coordinator for $groupId
     * @param string $groupId         Name of the group
     * @param array $topicPartitions  List of topic => partitions for fetching information
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
    public function fetchGroupOffsets(Node $coordinatorNode, $groupId, array $topicPartitions)
    {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new OffsetFetchRequest(
            $groupId,
            $topicPartitions,
            $this->configuration[ConsumerConfig::CLIENT_ID]
        );
        $request->writeTo($stream);
        $response = OffsetFetchResponse::unpack($stream);

        $result = [];
        foreach ($response->topics as $topic => $partitions) {
            /** @var Kafka\DTO\OffsetFetchPartition[] $partitions */
            foreach ($partitions as $partitionId => $partition) {
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
    public function joinGroup(Node $coordinatorNode, $groupId, $memberId, $protocolType, array $groupProtocols)
    {
        $stream = $coordinatorNode->getConnection($this->configuration);

        $request = new JoinGroupRequest(
            $groupId,
            $this->configuration[ConsumerConfig::SESSION_TIMEOUT_MS],
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
    public function leaveGroup(Node $coordinatorNode, $groupId, $memberId)
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
     * @param Node    $coordinatorNode  Current group coordinator for $groupId
     * @param string  $groupId          Name of the group
     * @param string  $memberId         Name of the group member
     * @param integer $generationId     Current generation of consumer
     * @param array   $groupAssignments Group assignments
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
    public function syncGroup(Node $coordinatorNode, $groupId, $memberId, $generationId, array $groupAssignments = [])
    {
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
    public function heartbeat(Node $coordinatorNode, $groupId, $memberId, $generationId)
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
     * @param string $groupId Name of the group
     *
     * @return Node
     *
     * @throws Kafka\Error\GroupCoordinatorNotAvailable
     * @throws Kafka\Error\GroupAuthorizationFailed
     */
    public function getGroupCoordinator($groupId)
    {
        // TODO: iterate over connections and wrap logic into the try..catch block
        /** @var Node $firstNode */
        $clusterNodes = $this->cluster->nodes();
        $firstNode    = reset($clusterNodes);
        $stream       = $firstNode->getConnection($this->configuration);

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
    }

    /**
     * Fetches messages from the specified topic and partitions
     *
     * @param array   $topicPartitionOffsets List of topic partition offsets as start point for fetching
     * @param integer $timeout               Timeout in ms to wait for fetching
     *
     * @return array
     *
     * @throws Kafka\Error\OffsetOutOfRange
     * @throws Kafka\Error\UnknownTopicOrPartition
     * @throws Kafka\Error\NotLeaderForPartition
     * @throws Kafka\Error\ReplicaNotAvailable
     * @throws Kafka\Error\UnknownError
     */
    public function fetch(array $topicPartitionOffsets, $timeout)
    {
        $timeout = min($this->configuration[ConsumerConfig::FETCH_MAX_WAIT_MS], $timeout);

        $result = $this->clusterRequest($topicPartitionOffsets, function (array $nodeTopicRequest) use ($timeout) {
            $request = new FetchRequest(
                $nodeTopicRequest,
                $timeout,
                $this->configuration[ConsumerConfig::FETCH_MIN_BYTES],
                $this->configuration[ConsumerConfig::MAX_PARTITION_FETCH_BYTES],
                -1,
                $this->configuration[ConsumerConfig::CLIENT_ID]
            );

            return $request;
        }, FetchResponse::class, function (array $result, FetchResponse $response) {
            foreach ($response->topics as $topic => $partitions) {
                foreach ($partitions as $partitionId => $responsePartition) {
                    /** @var Kafka\DTO\FetchResponsePartition $responsePartition */
                    if ($responsePartition->errorCode !== 0) {
                        throw KafkaException::fromCode($responsePartition->errorCode, compact('topic', 'partitionId'));
                    }
                    $result[$topic][$partitionId] = $responsePartition->messageSet;
                }
            }

            return $result;
        }, $timeout);

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
    public function fetchTopicPartitionOffsets(array $topicPartitions)
    {
        $result = $this->clusterRequest($topicPartitions, function (array $nodeTopicRequest) {
            $request = new OffsetsRequest(
                $nodeTopicRequest,
                1,
                -1,
                $this->configuration[ConsumerConfig::CLIENT_ID]
            );

            return $request;
        }, OffsetsResponse::class, function (array $result, OffsetsResponse $response) {
            foreach ($response->topics as $topic => $partitions) {
                /** @var Kafka\DTO\OffsetsPartition[] $partitions */
                foreach ($partitions as $partitionId => $partitionMetadata) {
                    if ($partitionMetadata->errorCode !== 0) {
                        throw KafkaException::fromCode($partitionMetadata->errorCode, compact('topic', 'partitionId'));
                    }
                    $result[$topic][$partitionId] = reset($partitionMetadata->offsets);
                }
            }

            return $result;
        });

        return $result;
    }

    private function clusterRequest(
        array $topicPartitionsRequest,
        \Closure $nodeRequest,
        $responseClass,
        \Closure $responseAggregator,
        $timeout = null
    ) {
        $requestByNode = [];

        foreach ($topicPartitionsRequest as $topic => $partitions) {
            foreach ($partitions as $partition => $partitionData) {
                $leaderNode = $this->cluster->leaderFor($topic, $partition);
                $requestByNode[$leaderNode->nodeId][$topic][$partition] = $partitionData;
            }
        }

        // TODO: Implement StreamGroup(Stream[] $connections) and Stream->joinGroup(StreamGroup $group)
        $socketAccessor = function (SocketStream $socket) {
            if (!$socket->isConnected) {
                $socket->connect();
            }

            return $socket->streamSocket;
        };
        $socketAccessor  = $socketAccessor->bindTo(null, SocketStream::class);
        $readNodeSockets = [];

        foreach ($requestByNode as $nodeId => $nodeTopicPartitions)
        {
            /** @var Record $request */
            $request = $nodeRequest($nodeTopicPartitions);
            $stream  = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);

            $readNodeSockets[$nodeId] = $socketAccessor($stream);
            $request->writeTo($stream);
        }

        $incompleteReads = $readNodeSockets;
        if (!isset($timeout)) {
            $timeout = $this->configuration[ConsumerConfig::REQUEST_TIMEOUT_MS];
        }
        $responses = [];
        while (!empty($incompleteReads)) {
            $readSelect  = $incompleteReads;
            $writeSelect = $exceptSelect = null;
            if (stream_select($readSelect, $writeSelect, $exceptSelect, intdiv($timeout, 1000), $timeout % 1000) > 0) {
                foreach ($readSelect as $resourceToRead) {
                    $nodeId             = array_search($resourceToRead, $readNodeSockets);
                    $connection         = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);
                    $responses[$nodeId] = $responseClass::unpack($connection);
                }
                $incompleteReads = array_diff($incompleteReads, $readSelect);
            }
        }
        $result = array_reduce($responses, $responseAggregator, []);

        return $result;
    }
}
