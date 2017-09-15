<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

/**
 * This API describes the valid offset range available for a set of topic-partitions.
 *
 * As with the produce and fetch APIs requests must be directed to the broker that is currently the leader for the
 * partitions in question. This can be determined using the metadata API.
 *
 * The response contains the starting offset of each segment for the requested partition as well as the "log end
 * offset" i.e. the offset of the next message that would be appended to the given partition.
 *
 * Since v2 if no topics (null input for list of topics) are provided, the offset information of all topics (or topic partitions) associated with the group is returned
 */
class OffsetFetchRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    const VERSION = 2;

    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    /**
     * @var array
     */
    private $topicPartitions;

    public function __construct($consumerGroup, array $topicPartitions = [], $clientId = '', $correlationId = 0)
    {
        $this->consumerGroup   = $consumerGroup;
        $this->topicPartitions = $topicPartitions;

        parent::__construct(Kafka::OFFSET_FETCH, $clientId, $correlationId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload     = parent::packPayload();
        $groupLength = strlen($this->consumerGroup);
        $totalTopics = count($this->topicPartitions) ?: -1;

        $payload .= pack("na{$groupLength}N", $groupLength, $this->consumerGroup, $totalTopics);
        foreach ($this->topicPartitions as $topic => $partitions) {
            $topicLength = strlen($topic);
            $payload .= pack("na{$topicLength}N", $topicLength, $topic, count($partitions));
            $packArgs = $partitions;
            array_unshift($packArgs, 'N*');
            $payload .= call_user_func_array('pack', $packArgs);
        }

        return $payload;
    }
}
