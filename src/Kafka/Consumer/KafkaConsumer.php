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

namespace Protocol\Kafka\Consumer;

use BadMethodCallException;
use InvalidArgumentException;
use Protocol\Kafka\Client;
use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\Common\PartitionMetadata;
use Protocol\Kafka\Consumer\Internals\SubscriptionState;
use Protocol\Kafka\DTO\RecordBatch;
use Protocol\Kafka\DTO\TopicPartitions;
use Protocol\Kafka\Error\EmptyAssignmentException;
use Protocol\Kafka\Error\KafkaException;
use Protocol\Kafka\Error\OffsetOutOfRange;
use Protocol\Kafka\Error\TopicPartitionRequestException;
use Protocol\Kafka\Error\UnknownTopicOrPartition;
use Protocol\Kafka\Record\JoinGroupRequest;
use Protocol\Kafka\Record\OffsetCommitRequest;
use Protocol\Kafka\Record\OffsetsRequest;
use Protocol\Kafka\Scheme;
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
    private $memberId = JoinGroupRequest::DEFAULT_MEMBER_ID;

    /**
     * Assigned consumer generation ID
     *
     * @var integer
     */
    private $generationId = OffsetCommitRequest::DEFAULT_GENERATION_ID;

    /**
     * Metadata for subscription
     *
     * @var SubscriptionState
     */
    private $subscriptionState;

    /**
     * Coordinator node
     *
     * @var Node
     */
    private $coordinator;

    /**
     * Last heartbeat time in ms
     *
     * @var integer
     */
    private $lastHeartbeatMs;

    /**
     * Last commit time in ms
     *
     * @var integer
     */
    private $lastAutoCommitMs;

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration + Config::getDefaultConfiguration();
        $assignorStrategy    = $this->configuration[Config::PARTITION_ASSIGNMENT_STRATEGY];

        if (!is_subclass_of($assignorStrategy, PartitionAssignorInterface::class)) {
            throw new InvalidArgumentException('Partition strategy class should implement PartitionAssignorInterface');
        }
        $this->assignorStrategy  = new $assignorStrategy();
        $this->subscriptionState = new SubscriptionState();
    }

    /**
     * Manually assign a list of partitions to this consumer. This interface does not allow for incremental assignment
     * and will replace the previous assignment (if there is one).
     *
     * If the given list of topic partitions is empty, it is treated the same as @see unsubscribe.
     *
     * Manual topic assignment through this method does not use the consumer's group management
     * functionality. As such, there will be no rebalance operation triggered when group membership or cluster and topic
     * metadata change. Note that it is not possible to use both manual partition assignment with @see assign
     * and group assignment with @see subscribe.
     *
     * If auto-commit is enabled, an async commit (based on the old assignment) will be triggered before the new
     * assignment replaces the old one.
     *
     * @param TopicPartitions[] $topicPartitions Key is topic and value is DTO with list of assigned partitions
     *
     * @return void
     */
    public function assign(array $topicPartitions)
    {
        if (empty($topicPartitions)) {
            $this->unsubscribe();
            return;
        }

        if ($this->configuration[Config::ENABLE_AUTO_COMMIT]) {
            // todo: this commit must be async
            $this->commitSync($this->subscriptionState->allConsumed());
        }

        $this->subscriptionState->assignFromUser($topicPartitions);
        $this->refreshTopicPartitionOffsets($topicPartitions);
    }

    /**
     * Get the set of topic partitions currently assigned to this consumer.
     *
     * @return array Key is topic and value is array of assigned partitions
     */
    public function assignment()
    {
        $result = [];
        foreach ($this->subscriptionState->getAssignment() as $topic => $partitions) {
            foreach ($partitions as $partitionId => $state) {
                $result[$topic][$partitionId] = $partitionId;
            }
        }

        return $result;
    }

    /**
     * Commit offsets returned on the last poll() for all the subscribed list of topics and partitions.
     *
     * @param array $topicPartitionOffsets Specified offsets for the specified list of topics and partitions.
     */
    public function commitSync(array $topicPartitionOffsets = null)
    {
        $topicPartitionOffsets = $topicPartitionOffsets ?? $this->subscriptionState->allConsumed();

        if (empty($topicPartitionOffsets)) {
            return;
        }

        $this->getClient()->commitGroupOffsets(
            $this->getCoordinator(),
            $this->configuration[Config::GROUP_ID],
            $this->memberId,
            $this->generationId,
            $topicPartitionOffsets,
            $this->configuration[Config::OFFSET_RETENTION_MS]
        );
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
        return $this->getCluster()->partitionsForTopic($topic);
    }

    /**
     * Suspend fetching from the requested partitions.
     *
     * @param int[] $topicPartitions List of topic partitions to suspend
     */
    public function pause(array $topicPartitions)
    {
        $this->subscriptionState->pause($topicPartitions);
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
     *
     * @return array
     */
    public function poll($timeout)
    {
        $milliSeconds = (int) (microtime(true) * 1e3);
        if (($milliSeconds - $this->lastHeartbeatMs) > $this->configuration[Config::HEARTBEAT_INTERVAL_MS]) {
            $this->heartbeat($milliSeconds);
        }

        $activeTopicPartitionOffsets = $this->subscriptionState->fetchablePartitions();

        $result = $this->fetchMessages($activeTopicPartitionOffsets, $timeout);

        $this->updateFetchPositions($result);

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
        return $this->subscriptionState->position($topic, $partition);
    }

    /**
     * Resume specified partitions which have been paused with pause($topicPartitions).
     *
     * @param array $topicPartitions List of topic partitions to resume
     */
    public function resume(array $topicPartitions)
    {
        $this->subscriptionState->resume($topicPartitions);
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
        $this->subscriptionState->seek($topic, $partition, $offset);
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
        if (empty($topics)) {
            $this->unsubscribe();
            return;
        }

        if (count($topics) !== count(array_filter($topics))) {
            throw new InvalidArgumentException('Topic collection to subscribe to cannot contain null or empty topic');
        }

        $coordinator = $this->getCoordinator();
        $this->subscriptionState->subscribeByTopics($topics);

        $joinResult = $this->getClient()->joinGroup(
            $coordinator,
            $this->configuration[Config::GROUP_ID],
            $this->memberId,
            'consumer',
            ['range' => new Subscription($topics)]
        );

        $this->memberId     = $joinResult->memberId;
        $this->generationId = $joinResult->generationId;

        $isLeader    = $joinResult->memberId === $joinResult->leaderId;
        $assignments = [];
        if ($isLeader) {
            $assignments = $this->assignorStrategy->assign($this->getCluster(), $joinResult->members);
        }
        $syncResult = $this->getClient()->syncGroup(
            $coordinator,
            $this->configuration[Config::GROUP_ID],
            $this->memberId,
            $this->generationId,
            $assignments
        );

        // TODO: Unpacking should be on scheme-level, instead of bytearray
        $assignmentData = new Stream\StringStream($syncResult->memberAssignment);
        $assignment     = Scheme::readObjectFromStream(MemberAssignment::class, $assignmentData);

        // TODO: Use $assignments->userData; $assignments->version
        $topicPartitions = $assignment->topicPartitions;

        if (empty($topicPartitions)) {
            throw new EmptyAssignmentException($topics);
        }

        $this->subscriptionState->assignFromSubscribed($topicPartitions);
        $this->refreshTopicPartitionOffsets($topicPartitions);
    }

    /**
     * Get the current subscription
     *
     * @return string
     */
    public function subscription()
    {
        return $this->subscriptionState->getSubscription();
    }

    /**
     * Unsubscribe from topics currently subscribed with subscribe(array $topics).
     *
     * This also clears any partitions directly assigned through assign(array $topicPartitions).
     */
    public function unsubscribe()
    {
        if ($this->subscriptionState->partitionsAutoAssigned()) {
            $this->getClient()->leaveGroup(
                $this->getCoordinator(),
                $this->configuration[Config::GROUP_ID],
                $this->memberId
            );
        }

        $this->subscriptionState->unsubscribe();

        $this->coordinator  = null;
        $this->memberId     = JoinGroupRequest::DEFAULT_MEMBER_ID;
        $this->generationId = OffsetCommitRequest::DEFAULT_GENERATION_ID;
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
        if (!$this->subscriptionState->partitionsAutoAssigned()) {
            return;
        }

        try {
            $this->getClient()->heartbeat(
                $this->getCoordinator(),
                $this->configuration[Config::GROUP_ID],
                $this->memberId,
                $this->generationId
            );
        } catch (KafkaException $e) {
            // Re-subscribe to the group in the case of failed heartbeat
            if ($this->subscriptionState->getSubscriptionType() === SubscriptionState::TYPE_AUTO_TOPICS) {
                $this->subscribe($this->subscriptionState->getSubscription());
            } elseif ($this->subscriptionState->getSubscriptionType() === SubscriptionState::TYPE_AUTO_PATTERN) {
                throw new BadMethodCallException('Pattern subscription is not implemented.');
            }
        }
        $this->lastHeartbeatMs = $heartBeatTimeMs; // Expect 64-bit platform PHP
    }

    /**
     * Verifies fetched partitions and asks broker for the latest/earliest offsets or throws an exception
     *
     * @param array $topicPartitionOffsets List of topic partitions
     *
     * @return array Existing or adjusted offsets (reloaded from the Kafka)
     */
    protected function autoResetOffsets(array $topicPartitionOffsets)
    {
        $result = $topicPartitionOffsets;

        $unknownTopicPartitions = $this->findUnknownTopicPartitions($topicPartitionOffsets);
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

        $assignment    = $this->assignment();
        $unknownTopics = array_diff_key($topicPartitions, $assignment);
        if (!empty($unknownTopics)) {
            throw new UnknownTopicOrPartition(compact('unknownTopics'));
        }
        foreach ($topicPartitions as $topic => $partitions) {
            $unknownPartitions = array_diff($partitions, $assignment[$topic]);
            if (!empty($unknownPartitions)) {
                throw new UnknownTopicOrPartition(compact('topic', 'unknownPartitions'));
            }
            $topicPartitionOffsetsRequest[$topic] = array_fill_keys($partitions, $requestType);
        }
        $topicPartitionOffsets = $this->getClient()->fetchTopicPartitionOffsets($topicPartitionOffsetsRequest);

        return $topicPartitionOffsets;
    }

    /**
     * This methods looks for the offsets in the returned RecordBatches and adjusts subscription state offsets
     *
     * @param RecordBatch[][][] $fetchResult Result from FetchResponse->topics
     *
     * @return void
     */
    protected function updateFetchPositions(array $fetchResult)
    {
        foreach ($fetchResult as $topic => $partitions) {
            foreach ($partitions as $partitionId => $recordBatches) {
                if (count($recordBatches) === 0) {
                    continue;
                }
                $lastRecordBatch = end($recordBatches);
                $lastOffset      = $lastRecordBatch->firstOffset + $lastRecordBatch->lastOffsetDelta;

                // original client uses position() method here
                $this->subscriptionState->seek($topic, $partitionId, $lastOffset + 1);
            }
        }
    }

    /**
     * Cluster lazy-loading
     *
     * @return Cluster
     */
    private function getCluster()
    {
        if (!$this->cluster) {
            $this->cluster = Cluster::bootstrap($this->configuration);
        }

        return $this->cluster;
    }

    /**
     * Lazy-loading for kafka client
     *
     * @return Client
     */
    private function getClient()
    {
        if (!$this->client) {
            $this->client = new Client($this->getCluster(), $this->configuration);
        }

        return $this->client;
    }

    /**
     * Updates consumer offsets in case of retention expiration
     *
     * @param array $activeTopicPartitionOffsets Current assignment state [topic][partition] => offsets
     * @param int   $timeout                     The time, in milliseconds, spent waiting in poll if data is not available.
     *                                           If 0, returns immediately with any records that are available now.
     *
     * @return array
     */
    private function fetchMessages(array $activeTopicPartitionOffsets, $timeout)
    {
        $exception = null;
        $result    = [];

        try {
            $result = $this->getClient()->fetch($activeTopicPartitionOffsets, $timeout);
        } catch (TopicPartitionRequestException $e) {
            $exception = $e;
            $result    = $e->getPartialResult();
        }

        if ($exception !== null) {
            $exceptions      = $exception->getExceptions();
            $topicPartitions = [];
            foreach ($exceptions as $topic => $partitions) {
                foreach ($partitions as $partitionId => $e) {
                    if ($e instanceof OffsetOutOfRange) {
                        $topicPartitions[$topic][$partitionId] = $partitionId;
                        unset($exceptions[$topic][$partitionId]);
                    }
                }

                if (empty($exceptions[$topic])) {
                    unset($exceptions[$topic]);
                }
            }

            if (!empty($topicPartitions)) {
                $actualOffsets = $this->getClient()->fetchTopicPartitionOffsets($topicPartitions);
                $unknownTopicPartitions = $this->findUnknownTopicPartitions($actualOffsets);
                if (!empty($unknownTopicPartitions)) {
                    $fetchedPositions = $this->fetchOffsetAndSeek($unknownTopicPartitions, OffsetsRequest::EARLIEST);
                    $actualOffsets    = array_replace_recursive($actualOffsets, $fetchedPositions);
                }

                $this->commitSync($actualOffsets);

                $fetchResult = $this->getClient()->fetch($actualOffsets, $timeout);
                $result      = array_replace_recursive($result, $fetchResult);
            }
        }

        if (!empty($exceptions)) {
            throw new TopicPartitionRequestException($result, $exceptions);
        }

        return $result;
    }

    /**
     * Look for topic and partitions without assigned offset
     *
     * @param array $topicPartitionOffsets Array of [topic][partition] => offset
     *
     * @return array
     */
    protected function findUnknownTopicPartitions(array $topicPartitionOffsets)
    {
        $unknownTopicPartitions = [];
        foreach ($topicPartitionOffsets as $topic => $partitionOffsets) {
            $unknownPartitionOffsets = array_keys($partitionOffsets, -1, true);
            if (!empty($unknownPartitionOffsets)) {
                $unknownTopicPartitions[$topic] = $unknownPartitionOffsets;
            }
        }

        return $unknownTopicPartitions;
    }

    /**
     * Return group coordinator
     *
     * @return Node
     */
    protected function getCoordinator()
    {
        if (!$this->coordinator) {
            $groupId           = $this->configuration[Config::GROUP_ID];
            $this->coordinator = $this->getClient()->getGroupCoordinator($groupId);
        }

        return $this->coordinator;
    }

    /**
     * Reads topic-partition offsets and stores them into internal data
     *
     * @param TopicPartitions[] $topicPartitions List of Topic => TopicPartitions
     *
     * @return void
     */
    private function refreshTopicPartitionOffsets(array $topicPartitions)
    {
        $topicPartitionOffsets = $this->getClient()->fetchGroupOffsets(
            $this->getCoordinator(),
            $this->configuration[Config::GROUP_ID],
            $topicPartitions
        );

        $offsets = $this->autoResetOffsets($topicPartitionOffsets);
        foreach ($offsets as $topic => $partitions) {
            foreach ($partitions as $partition => $offset) {
                $this->subscriptionState->seek($topic, $partition, $offset);
            }
        }
    }
}
