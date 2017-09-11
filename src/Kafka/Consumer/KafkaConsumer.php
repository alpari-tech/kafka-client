<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka\Client;
use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\Common\PartitionMetadata;
use Protocol\Kafka\DTO\MessageSet;
use Protocol\Kafka\Error\KafkaException;
use Protocol\Kafka\Error\OffsetOutOfRange;
use Protocol\Kafka\Error\UnknownTopicOrPartition;
use Protocol\Kafka\Record\OffsetsRequest;
use Protocol\Kafka\Stream;

/**
 * A Kafka client that consumes records from a Kafka cluster.
 */
class KafkaConsumer
{
    /**
     * The producer configs
     *
     * @var array
     */
    private $configuration = [];

    /**
     * Kafka cluster configuration
     *
     * @var Cluster
     */
    private $cluster;

    /**
     * Assignor strategy
     *
     * @var PartitionAssignorInterface
     */
    private $assignorStrategy;

    /**
     * Low-level kafka client
     *
     * @var Client
     */
    private $client;

    /**
     * Assigned memberId for this consumer
     *
     * @var string
     */
    private $memberId;

    /**
     * Assigned consumer generation ID
     *
     * @var integer
     */
    private $generationId;

    /**
     * Metadata for subscribed topics
     *
     * @var Subscription
     */
    private $subscription;

    /**
     * List of assigned topic partitions
     *
     * @var array
     */
    private $assignedTopicPartitions = [];

    /**
     * List of paused topic partitions
     *
     * @var array
     */
    private $pausedTopicPartitions = [];

    /**
     * Offsets for topic partitions in the consumer group
     *
     * @var array
     */
    private $topicPartitionOffsets = [];

    /**
     * Coordinator node
     *
     * @var Node
     */
    private $coordinator;

    /**
     * Last hearbeat time in ms
     *
     * @var integer
     */
    private $lastHearbeatMs;

    /**
     * Last commit time in ms
     *
     * @var integer
     */
    private $lastAutoCommitMs;

    /**
     * Default configuration for producer
     *
     * @var array
     */
    private static $defaultConfiguration = [
        /* Used configs */
        Config::BOOTSTRAP_SERVERS             => [],
        Config::CLIENT_ID                     => 'PHP/Kafka',
        Config::GROUP_ID                      => '',
        Config::PARTITION_ASSIGNMENT_STRATEGY => RoundRobinAssignor::class,
        Config::SESSION_TIMEOUT_MS            => 30000,
        Config::FETCH_MIN_BYTES               => 1,
        Config::FETCH_MAX_WAIT_MS             => 500,
        Config::MAX_PARTITION_FETCH_BYTES     => 65536,
        Config::AUTO_OFFSET_RESET             => OffsetResetStrategy::LATEST,
        Config::REQUEST_TIMEOUT_MS            => 2000,
        Config::HEARTBEAT_INTERVAL_MS         => 2000,
        Config::ENABLE_AUTO_COMMIT            => true,
        Config::AUTO_COMMIT_INTERVAL_MS       => 0, // Commit always after each poll()
        Config::OFFSET_RETENTION_MS           => -1, // Use broker retention time for offsets
        Config::STREAM_PERSISTENT_CONNECTION  => false,
        Config::STREAM_ASYNC_CONNECT          => false,
        Config::METADATA_MAX_AGE_MS           => 300000,
        Config::RECEIVE_BUFFER_BYTES          => 65536,
        Config::SEND_BUFFER_BYTES             => 131072,

        Config::SSL_KEY_PASSWORD          => null,
        Config::SSL_KEYSTORE_LOCATION     => null,
        Config::SSL_KEYSTORE_PASSWORD     => null,
        Config::CONNECTIONS_MAX_IDLE_MS   => 540000,
        Config::SASL_MECHANISM            => 'GSSAPI',
        Config::SECURITY_PROTOCOL         => 'plaintext',
        Config::RECONNECT_BACKOFF_MS      => 50,
        Config::RETRY_BACKOFF_MS          => 100,
    ];

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration + self::$defaultConfiguration;
        $this->cluster       = Cluster::bootstrap($this->configuration);
        $this->client        = new Client($this->cluster, $this->configuration);
        $assignorStrategy    = $this->configuration[Config::PARTITION_ASSIGNMENT_STRATEGY];

        if (!is_subclass_of($assignorStrategy, PartitionAssignorInterface::class)) {
            throw new \InvalidArgumentException('Partition strategy class should implement PartitionAssignorInterface');
        }
        $this->assignorStrategy = new $assignorStrategy;
    }

    /**
     * Assign a list of partitions to this consumer.
     *
     * @param array $topicPartitions Key is topic and value is array of assigned partitions
     */
    public function assign(array $topicPartitions)
    {
        if (empty($topicPartitions)) {
            throw new \InvalidArgumentException(
                'Can not assign empty list of topic partitions to the consumer.'.
                'Probably, not enough partitions for this topic.'
            );
        }
        $unknownTopics = array_diff(array_keys($topicPartitions), $this->subscription->topics);
        if (!empty($unknownTopics)) {
            throw new UnknownTopicOrPartition(compact('unknownTopics'));
        }
        $this->assignedTopicPartitions = $topicPartitions;

        $topicPartitionOffsets = $this->client->fetchGroupOffsets(
            $this->coordinator,
            $this->configuration[Config::GROUP_ID],
            $topicPartitions
        );
        $this->topicPartitionOffsets = $this->autoResetOffsets($topicPartitionOffsets);
    }

    /**
     * Get the set of topic partitions currently assigned to this consumer.
     *
     * @return array Key is topic and value is array of assigned partitions
     */
    public function assignment()
    {
        return $this->assignedTopicPartitions;
    }

    /**
     * Commit offsets returned on the last poll() for all the subscribed list of topics and partitions.
     *
     * @param array $topicPartitionOffsets Specified offsets for the specified list of topics and partitions.
     */
    public function commitSync(array $topicPartitionOffsets = null)
    {
        $topicPartitionOffsets = isset($topicPartitionOffsets) ? $topicPartitionOffsets : $this->topicPartitionOffsets;

        $this->client->commitGroupOffsets(
            $this->coordinator,
            $this->configuration[Config::GROUP_ID],
            $this->memberId,
            $this->generationId,
            $topicPartitionOffsets,
            $this->configuration[Config::OFFSET_RETENTION_MS]
        );

        $this->topicPartitionOffsets = $topicPartitionOffsets;
    }

    /**
     * Gets the partition metadata for the given topic.
     *
     * @param string $topic
     *
     * @return PartitionMetadata[]
     */
    public function partitionsFor($topic)
    {
        return $this->cluster->partitionsForTopic($topic);
    }

    /**
     * Suspend fetching from the requested partitions.
     *
     * @param array $topicPartitions List of topic partitions to suspend
     */
    public function pause(array $topicPartitions)
    {
        $this->pausedTopicPartitions = $topicPartitions;
    }

    /**
     * Fetches data for the topics or partitions specified using one of the subscribe/assign APIs.
     *
     * It is an error to not have subscribed to any topics or partitions before polling for data.
     *
     * On each poll, consumer will try to use the last consumed offset as the starting offset and fetch sequentially.
     * The last consumed offset can be manually set through seek(topic, partition, long) or automatically set as the
     * last committed offset for the subscribed list of partitions
     *
     * @param integer $timeout The time, in milliseconds, spent waiting in poll if data is not available.
     *                         If 0, returns immediately with any records that are available now.
     */
    public function poll($timeout)
    {
        $milliSeconds = (int) (microtime(true) * 1e3);
        if (($milliSeconds - $this->lastHearbeatMs) > $this->configuration[Config::HEARTBEAT_INTERVAL_MS]) {
            $this->heartbeat($milliSeconds);
        }

        $activeTopicPartitionOffsets = $this->topicPartitionOffsets;
        foreach ($this->pausedTopicPartitions as $topic => $partitions) {
            // This can be optimized in pause()/resume methods
            $activeTopicPartitionOffsets[$topic] = array_diff($activeTopicPartitionOffsets[$topic], $partitions);
        }
        $result = $this->client->fetch($activeTopicPartitionOffsets, $timeout);

        $resultOffsets = $this->fetchResultOffsets($result);
        if ($resultOffsets) {
            $this->topicPartitionOffsets = array_replace_recursive($this->topicPartitionOffsets, $resultOffsets);
        }

        if ($this->configuration[Config::ENABLE_AUTO_COMMIT]) {
            if (($milliSeconds - $this->lastAutoCommitMs) > $this->configuration[Config::AUTO_COMMIT_INTERVAL_MS]) {
                $this->commitSync();
                $this->lastAutoCommitMs = $milliSeconds;
            }
        }

        return $result;
    }

    /**
     * Get the offset of the next record that will be fetched (if a record with that offset exists).
     *
     * @param string $topic Name of the topic
     * @param integer $partition Id of partition
     *
     * @return integer
     */
    public function position($topic, $partition)
    {
        if (!isset($this->assignedTopicPartitions[$topic][$partition])) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        return $this->topicPartitionOffsets[$topic][$partition] + 1;
    }

    /**
     * Resume specified partitions which have been paused with pause($topicPartitions).
     *
     * @param array $topicPartitions List of topic partitions to resume
     */
    public function resume(array $topicPartitions)
    {
        foreach ($topicPartitions as $topic => $partitions) {
            if (isset($this->pausedTopicPartitions[$topic])) {
                $this->pausedTopicPartitions[$topic] = array_diff($this->pausedTopicPartitions['topic'], $partitions);
            }
        }
    }

    /**
     * Overrides the fetch offsets that the consumer will use on the next poll(timeout).
     *
     * @param string $topic Name of the topic
     * @param integer $partition Id of partition
     * @param integer $offset New offset value
     */
    public function seek($topic, $partition, $offset)
    {
        if (!isset($this->assignedTopicPartitions[$topic][$partition])) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }
        $this->topicPartitionOffsets[$topic][$partition] = $offset;
    }

    /**
     * Seek to the first offset for each of the given partitions.
     *
     * @param array $topicPartitions
     */
    public function seekToBeginning(array $topicPartitions)
    {
        $this->fetchOffsetAndSeek($topicPartitions, OffsetsRequest::EARLIEST);
    }

    /**
     * Seek to the last offset for each of the given partitions.
     *
     * @param array $topicPartitions
     */
    public function seekToEnd(array $topicPartitions)
    {
        $this->fetchOffsetAndSeek($topicPartitions, OffsetsRequest::LATEST);
    }

    /**
     * Subscribe to the given list of topics to get dynamically assigned partitions.
     *
     * @param array $topics List of topics to subscribe
     */
    public function subscribe(array $topics)
    {
        $groupId           = $this->configuration[Config::GROUP_ID];
        $this->coordinator = $this->client->getGroupCoordinator($groupId);

        $subscription = Subscription::fromSubscription($topics);
        $joinResult   = $this->client->joinGroup(
            $this->coordinator,
            $this->configuration[Config::GROUP_ID],
            $this->memberId,
            'consumer',
            ['range' => $subscription]
        );

        $this->memberId     = $joinResult->memberId;
        $this->generationId = $joinResult->generationId;

        $isLeader = $joinResult->memberId === $joinResult->leaderId;

        if ($isLeader) {
            $groupAssignments = $this->assignorStrategy->assign($this->cluster, $joinResult->members);
            $syncResult       = $this->client->syncGroup(
                $this->coordinator,
                $this->configuration[Config::GROUP_ID],
                $this->memberId,
                $this->generationId,
                $groupAssignments
            );
            $topicPartitions = $groupAssignments[$this->memberId]->topicPartitions;
        } else {
            $syncResult = $this->client->syncGroup(
                $this->coordinator,
                $this->configuration[Config::GROUP_ID],
                $this->memberId,
                $this->generationId
            );

            $assignments = MemberAssignment::unpack(new Stream\StringStream($syncResult->memberAssignment));

            // TODO: Use $assignments->userData; $assignments->version;
            $topicPartitions = $assignments->topicPartitions;
        }
        $this->subscription = $subscription;
        $this->assign($topicPartitions);
    }

    /**
     * Get the current subscription
     *
     * @return Subscription
     */
    public function subscription()
    {
        return $this->subscription;
    }

    /**
     * Unsubscribes from topics currently subscribed with subscribe(array $topics).
     *
     * This also clears any partitions directly assigned through assign(array $topicPartitions).
     */
    public function unsubscribe()
    {
        if (!empty($this->subscription)) {
            $this->client->leaveGroup(
                $this->coordinator,
                $this->configuration[Config::GROUP_ID],
                $this->memberId
            );
            unset($this->subscription);
        }

        $this->assignedTopicPartitions = [];
        $this->topicPartitionOffsets   = [];
    }

    /**
     * Automatic consumer destruction should invoke unsubscription process
     */
    public function __destruct()
    {
        $this->unsubscribe();
    }

    /**
     * Performs a heartbeat for the group
     *
     * @param int $heartBeatTimeMs timestamp in ms (microtime(true) * 100)
     */
    protected function heartbeat($heartBeatTimeMs)
    {
        try {
            $this->client->heartbeat(
                $this->coordinator,
                $this->configuration[Config::GROUP_ID],
                $this->memberId,
                $this->generationId
            );
        } catch (KafkaException $e) {
            // Re-subscribe to the group in the case of failed heartbeat
            $this->subscribe($this->subscription->topics);
        }
        $this->lastHearbeatMs = $heartBeatTimeMs; // Expect 64-bit platform PHP
    }

    /**
     * Verifies fetched partitions and asks broker for the latest/earlisest offsets or throws an exception
     *
     * @param array $topicPartitionOffsets List of topic partitions
     *
     * @return array Existing or adjusted offsets (reloaded from the Kafka)
     */
    protected function autoResetOffsets(array $topicPartitionOffsets)
    {
        $result = $topicPartitionOffsets;

        $unknownTopicPartitions = [];
        foreach ($topicPartitionOffsets as $topic => $partitionOffsets) {
            $unknownPartitionOffsets = array_keys($partitionOffsets, -1, true);
            if (!empty($unknownPartitionOffsets)) {
                $unknownTopicPartitions[$topic] = $unknownPartitionOffsets;
            }
        }
        if (empty($unknownTopicPartitions)) {
            return $result;
        }
        switch ($this->configuration[Config::AUTO_OFFSET_RESET]) {
            case OffsetResetStrategy::LATEST:
                $fetchedOffsets = $this->fetchOffsetAndSeek($unknownTopicPartitions, OffsetsRequest::LATEST);
                break;
            case OffsetResetStrategy::EARLIEST:
                $fetchedOffsets = $this->fetchOffsetAndSeek($unknownTopicPartitions, OffsetsRequest::EARLIEST);
                break;
            default:
                throw new OffsetOutOfRange(compact('unknownTopicPartitions'));
        }

        return array_replace_recursive($topicPartitionOffsets, $fetchedOffsets);
    }

    /**
     * Fetches offsets for specific topics and partitions
     *
     * @param array   $topicPartitions List of topic and partitions
     * @param integer $requestType     Offset type, e.g. OffsetsRequest::EARLIEST
     *
     * @return array
     */
    protected function fetchOffsetAndSeek(array $topicPartitions, $requestType)
    {
        $topicPartitionOffsetsRequest = [];

        $unknownTopics = array_diff_key($topicPartitions, $this->assignedTopicPartitions);
        if (!empty($unknownTopics)) {
            throw new UnknownTopicOrPartition(compact('unknownTopics'));
        }
        foreach ($topicPartitions as $topic => $partitions) {
            $unknownPartitions = array_diff($partitions, $this->assignedTopicPartitions[$topic]);
            if (!empty($unknownPartitions)) {
                throw new UnknownTopicOrPartition(compact('topic', 'unknownPartitions'));
            }
            $topicPartitionOffsetsRequest[$topic] = array_fill_keys($partitions, $requestType);
        }
        $topicPartitionOffsets = $this->client->fetchTopicPartitionOffsets($topicPartitionOffsetsRequest);

        return $topicPartitionOffsets;
    }

    /**
     * This methods looks for the offsets in the returned MessageSets and returns them incremented
     *
     * @param array $fetchResult Result from FetchResponse->topics
     *
     * @return array Last offsets, returned from the poll()
     */
    protected function fetchResultOffsets(array $fetchResult)
    {
        $result = [];

        foreach ($fetchResult as $topic => $partitions) {
            foreach ($partitions as $partitionId => $messageSet) {
                if (empty($messageSet)) {
                    continue;
                }
                /** @var MessageSet $lastMessage */
                $lastMessage = end($messageSet);
                $result[$topic][$partitionId] = $lastMessage->offset + 1;
            }
        }

        return $result;
    }
}
