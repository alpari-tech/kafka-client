<?php

namespace Protocol\Kafka\Consumer\Internals;

use InvalidArgumentException;
use Protocol\Kafka\Error\UnknownTopicOrPartition;

/**
 * Class SubscriptionState
 */
final class SubscriptionState
{
    /**
     * No subscription type has been defined yet
     */
    const TYPE_NONE = 0;

    /**
     * This subscription is using by consumer->subscribe
     */
    const TYPE_AUTO_TOPICS = 1;

    /**
     * This subscription is made by pattern
     */
    const TYPE_AUTO_PATTERN = 2;

    /**
     * Subscription is assigned manually
     */
    const TYPE_USER_ASSIGNED = 3;

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
     *
     * @return int
     */
    public function getSubscriptionType()
    {
        return $this->subscriptionType;
    }

    /**
     * Assigns partitions manually
     *
     * @param array $topicPartitions Array in form string => int[], where key is topic name and value is array of partitions
     *
     * @return void
     */
    public function assignFromUser(array $topicPartitions)
    {
        $this->setSubscriptionType(self::TYPE_USER_ASSIGNED);
        $this->setAssignment($topicPartitions);
    }

    /**
     * Subscribes to list of given topics
     *
     * @param string[] $topics List of topics to subscribe
     *
     * @return void
     */
    public function subscribeByTopics(array $topics)
    {
        $this->setSubscriptionType(self::TYPE_AUTO_TOPICS);
        $this->subscription = array_flip($topics);
    }

    /**
     * Assigns topic-partitions from data, received from group-coordinator
     *
     * @param array $assignments Array where key is the topic name, and value is array of partitions
     *
     * @return void
     */
    public function assignFromSubscribed(array $assignments)
    {
        if (!$this->partitionsAutoAssigned()) {
            throw new InvalidArgumentException("Attempt to dynamically assign partitions while manual assignment in use");
        }

        if ($this->subscribedPattern !== null) {
            $topicPartitionMessage = '';
            foreach ($assignments as $topic => $partitions) {
                if (!preg_match($this->subscribedPattern, $topic)) {
                    $topicPartitionMessage .= sprintf(
                        "topic \"%s\", partitions: %s\n",
                        $topic,
                        implode(', ', $partitions)
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
            $unknownTopics = array_diff_key($assignments, $this->subscription);
            if (!empty($unknownTopics)) {
                $topicPartitionMessage = '';
                foreach ($unknownTopics as $topic => $partitions) {
                    $topicPartitionMessage .= sprintf(
                        "topic \"%s\", partitions: %s\n",
                        $topic,
                        implode(', ', $partitions)
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
    public function getSubscription()
    {
        return array_keys($this->subscription);
    }

    /**
     * Return array indexed by topic name and value with assigned partitions, if this is manual subscription
     *
     * @return array
     */
    public function getAssignment()
    {
        return $this->assignment;
    }

    /**
     * Return pattern regex for subscribed topics in case if consumer is subscribed by pattern
     *
     * @return string|null
     */
    public function getSubscribedPattern()
    {
        return $this->subscribedPattern;
    }

    /**
     * Unsubscribe from all previous settings
     *
     * @return void
     */
    public function unsubscribe()
    {
        $this->subscriptionType  = self::TYPE_NONE;
        $this->assignment        = [];
        $this->subscribedPattern = null;
        $this->subscription      = [];
    }

    /**
     * Test if given topic-partition is assigned to this subscription
     *
     * @param string $topic     Topic name
     * @param int    $partition Partition within given topic
     *
     * @return bool
     */
    public function isAssigned($topic, $partition)
    {
        return isset($this->assignment[$topic][$partition]);
    }

    /**
     * Return true if this is an auto-assigned subscription, false otherwise
     *
     * @return bool
     */
    public function partitionsAutoAssigned()
    {
        return $this->subscriptionType === self::TYPE_AUTO_PATTERN ||
            $this->subscriptionType === self::TYPE_AUTO_TOPICS;
    }

    /**
     * Return topic-partitions that are allowed to fetch from broker.
     *
     * @return array [topic-name:string][partition:int] -> offset
     */
    public function fetchablePartitions()
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
    public function allConsumed()
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
     * Overrides the fetch offsets that the consumer will use on the next poll(timeout).
     *
     * @param string  $topic     Name of the topic
     * @param integer $partition Id of partition
     * @param integer $offset    New offset value
     *
     * @return void
     */
    public function seek($topic, $partition, $offset)
    {
        if (!$this->isAssigned($topic, $partition)) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        $this->assignment[$topic][$partition]['position'] = $offset;
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
        if (!$this->isAssigned($topic, $partition)) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        return $this->assignment[$topic][$partition]['position'];
    }

    /**
     * Pause consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to pause
     *
     * @return void
     */
    public function pause(array $topicPartitions)
    {
        $this->pauseConsumption($topicPartitions, true);
    }

    /**
     * Resume consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to resume
     *
     * @return void
     */
    public function resume(array $topicPartitions)
    {
        $this->pauseConsumption($topicPartitions, false);
    }

    /**
     * Changes type of this state
     *
     * @param int $type New type, must be one of self::TYPE_* constants
     *
     * @return void
     */
    private function setSubscriptionType($type)
    {
        if ($this->subscriptionType === self::TYPE_NONE) {
            $this->subscriptionType = $type;
        }

        if ($this->subscriptionType !== $type) {
            throw new InvalidArgumentException(
                "Subscription to topics, partitions and pattern are mutually exclusive"
            );
        }
    }

    /**
     * Sets assignment for this subscription
     *
     * @param array $assignment Assignment in form [topic name:string][partition:int] -> state
     *
     * @return void
     */
    private function setAssignment(array $assignment)
    {
        $targetAssignment = [];
        foreach ($assignment as $topic => $partitions) {
            foreach ($partitions as $partitionId => $noMatter) {
                $targetAssignment[$topic][$partitionId] = isset($this->assignment[$topic][$partitionId])
                    ? $this->assignment[$topic][$partitionId]
                    : ['position' => null, 'isPaused' => false];
            }
        }

        $this->assignment = $targetAssignment;
    }

    /**
     * Return string containing all current assignments
     *
     * @param string $separator Separator between assignments
     *
     * @return string
     */
    private function formatAssignment($separator = ', ')
    {
        $result = '';
        foreach ($this->assignment as $topic => $partitions) {
            foreach ($partitions as $partition => $state) {
                $result .= sprintf("%s:%s%s", $topic, $partition, $separator);
            }
        }

        return $result;
    }

    /**
     * Resume consumption for given topic and partition
     *
     * @param array $topicPartitions Array of topic-partitions to pause/resume
     * @param bool  $isPaused        Flag whether we want to pause (true) or resume (false) consumption
     *
     * @return void
     */
    private function pauseConsumption(array $topicPartitions, $isPaused)
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
