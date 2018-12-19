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

namespace Alpari\Kafka\Consumer\Internals;

use Alpari\Kafka\DTO\TopicPartitions;
use Alpari\Kafka\Error\UnknownTopicOrPartition;
use InvalidArgumentException;

/**
 * Class SubscriptionState
 */
final class SubscriptionState
{
    /**
     * No subscription type has been defined yet
     */
    public const TYPE_NONE = 0;

    /**
     * This subscription is using by consumer->subscribe
     */
    public const TYPE_AUTO_TOPICS = 1;

    /**
     * This subscription is made by pattern
     */
    public const TYPE_AUTO_PATTERN = 2;

    /**
     * Subscription is assigned manually
     */
    public const TYPE_USER_ASSIGNED = 3;

    /**
     * Array of subscribed topics, topic name is a key, value does not make sense
     *
     * @var string[]
     */
    private $subscription = [];

    /**
     * Assigned partitions [string][int] => state
     *
     * @var array
     */
    private $assignment = [];

    /**
     * Pattern used for subscribing
     *
     * @var string
     */
    private $subscribedPattern;

    /**
     * Type of this subscription, one of TYPE_* constant
     *
     * @var int
     */
    private $subscriptionType = self::TYPE_NONE;

    /**
     * Return type of this subscription
     */
    public function getSubscriptionType(): int
    {
        return $this->subscriptionType;
    }

    /**
     * Assigns partitions manually
     *
     * @param TopicPartitions[] $topicPartitions Array where key is topic name and value DTO with partitions
     */
    public function assignFromUser(array $topicPartitions): void
    {
        $this->setSubscriptionType(self::TYPE_USER_ASSIGNED);
        $this->setAssignment($topicPartitions);
    }

    /**
     * Subscribes to list of given topics
     *
     * @param string[] $topics List of topics to subscribe
     */
    public function subscribeByTopics(array $topics): void
    {
        $this->setSubscriptionType(self::TYPE_AUTO_TOPICS);
        $this->subscription = array_flip($topics);
    }

    /**
     * Assigns topic-partitions from data, received from group-coordinator
     *
     * @param TopicPartitions[] $assignments Array where key is the topic name and value DTO with partitions
     */
    public function assignFromSubscribed(array $assignments): void
    {
        if (!$this->partitionsAutoAssigned()) {
            throw new InvalidArgumentException('Attempt to dynamically assign partitions while manual assignment in use');
        }

        if ($this->subscribedPattern !== null) {
            $topicPartitionMessage = '';
            foreach ($assignments as $topic => $topicPartitions) {
                if (!preg_match($this->subscribedPattern, $topic)) {
                    $topicPartitionMessage .= sprintf(
                        "topic \"%s\", partitions: %s\n",
                        $topic,
                        implode(', ', $topicPartitions->partitions)
                    );
                }
            }

            if (!empty($topicPartitionMessage)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Assigned partitions \n %s \n for non-subscribed topic regex pattern; subscription pattern is %s",
                        $topicPartitionMessage,
                        $this->subscribedPattern
                    )
                );
            }
        } else {
            /** @var TopicPartitions[] $unknownTopics */
            $unknownTopics = array_diff_key($assignments, $this->subscription);
            if (!empty($unknownTopics)) {
                $topicPartitionMessage = '';
                foreach ($unknownTopics as $topic => $topicPartitions) {
                    $topicPartitionMessage .= sprintf(
                        "topic \"%s\", partitions: %s\n",
                        $topic,
                        implode(', ', $topicPartitions->partitions)
                    );
                }
                throw new InvalidArgumentException(
                    sprintf(
                        "Assigned partitions \n %s \n for non-subscribed topic; subscription is \"%s\".",
                        $topicPartitionMessage,
                        implode('", "', $this->getSubscription())
                    )
                );
            }
        }

        $this->setAssignment($assignments);
    }

    /**
     * Return list of subscribed topics in case of auto-subscription
     *
     * @return string[]
     */
    public function getSubscription(): array
    {
        return array_keys($this->subscription);
    }

    /**
     * Return array indexed by topic name and value with assigned partitions, if this is manual subscription
     */
    public function getAssignment(): array
    {
        return $this->assignment;
    }

    /**
     * Return pattern regex for subscribed topics in case if consumer is subscribed by pattern or null if no pattern
     */
    public function getSubscribedPattern(): ?string
    {
        return $this->subscribedPattern;
    }

    /**
     * Unsubscribe from all previous settings
     */
    public function unsubscribe(): void
    {
        $this->subscriptionType  = self::TYPE_NONE;
        $this->assignment        = [];
        $this->subscribedPattern = null;
        $this->subscription      = [];
    }

    /**
     * Test if given topic-partition is assigned to this subscription
     */
    public function isAssigned(string $topic, int $partition): bool
    {
        return isset($this->assignment[$topic][$partition]);
    }

    /**
     * Return true if this is an auto-assigned subscription, false otherwise
     */
    public function partitionsAutoAssigned(): bool
    {
        $isAutoPattern = $this->subscriptionType === self::TYPE_AUTO_PATTERN;
        $isAutoTopics  = $this->subscriptionType === self::TYPE_AUTO_TOPICS;

        return $isAutoPattern || $isAutoTopics;
    }

    /**
     * Return topic-partitions that are allowed to fetch from broker.
     *
     * @return array [topic-name:string][partition:int] -> offset
     */
    public function fetchablePartitions(): array
    {
        $result = [];
        foreach ($this->assignment as $topic => $partitions) {
            foreach ($partitions as $partition => $state) {
                if (!$state['isPaused']) {
                    $result[$topic][$partition] = $state['position'];
                }
            }
        }

        return $result;
    }

    /**
     * Return topic-partitions-offsets this consumer is assigned to.
     *
     * @return array [topic name: string][partition: int] -> offset:int
     */
    public function allConsumed(): array
    {
        $result = [];
        foreach ($this->assignment as $topic => $partitions) {
            foreach ($partitions as $partition => $state) {
                if ($state['position'] !== null) {
                    $result[$topic][$partition] = $state['position'];
                }
            }
        }

        return $result;
    }

    /**
     * Overrides the fetch offsets that the consumer will use on the next poll().
     *
     * @param string  $topic     Name of the topic
     * @param integer $partition Id of partition
     * @param integer $offset    New offset value
     *
     * @return void
     */
    public function seek(string $topic, int $partition, int $offset): void
    {
        if (!$this->isAssigned($topic, $partition)) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        $this->assignment[$topic][$partition]['position'] = $offset;
    }

    /**
     * Get the offset of the next record that will be fetched (if a record with that offset exists).
     */
    public function position(string $topic, int $partition): int
    {
        if (!$this->isAssigned($topic, $partition)) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        return $this->assignment[$topic][$partition]['position'];
    }

    /**
     * Pause consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to pause
     */
    public function pause(array $topicPartitions): void
    {
        $this->pauseConsumption($topicPartitions, true);
    }

    /**
     * Resume consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to resume
     */
    public function resume(array $topicPartitions): void
    {
        $this->pauseConsumption($topicPartitions, false);
    }

    /**
     * Changes type of this state
     *
     * @param int $type New type, must be one of self::TYPE_* constants
     */
    private function setSubscriptionType(int $type): void
    {
        if ($this->subscriptionType === self::TYPE_NONE) {
            $this->subscriptionType = $type;
        }

        if ($this->subscriptionType !== $type) {
            throw new InvalidArgumentException(
                'Subscription to topics, partitions and pattern are mutually exclusive'
            );
        }
    }

    /**
     * Sets assignment for this subscription
     *
     * @var TopicPartitions[] $assignment Array where key is topic name and value DTO with partitions
     */
    private function setAssignment(array $assignment): void
    {
        $targetAssignment = [];
        foreach ($assignment as $topic => $topicPartitions) {
            foreach ($topicPartitions->partitions as $partitionId) {
                $assignment = $this->assignment[$topic][$partitionId] ?? ['position' => null, 'isPaused' => false];

                $targetAssignment[$topic][$partitionId] = $assignment;
            }
        }

        $this->assignment = $targetAssignment;
    }

    /**
     * Return string containing all current assignments
     */
    private function formatAssignment(string $separator = ', '): string
    {
        $result = '';
        foreach ($this->assignment as $topic => $partitions) {
            foreach ($partitions as $partition => $state) {
                $result .= sprintf('%s:%s%s', $topic, $partition, $separator);
            }
        }

        return $result;
    }

    /**
     * Resume consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to pause/resume
     * @param bool  $isPaused        Flag whether we want to pause (true) or resume (false) consumption
     */
    private function pauseConsumption(array $topicPartitions, bool $isPaused): void
    {
        foreach ($topicPartitions as $topic => $partitionIds) {
            foreach ($partitionIds as $partitionId => $noMatter) {
                if (!$this->isAssigned($topic, $partitionId)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "Paused partition \n %s:%s \n for non-subscribed topic; assignment is %s",
                            $topic,
                            $partitionId,
                            $this->formatAssignment()
                        )
                    );
                }

                $this->assignment[$topic][$partitionId]['isPaused'] = $isPaused;
            }
        }
    }
}
