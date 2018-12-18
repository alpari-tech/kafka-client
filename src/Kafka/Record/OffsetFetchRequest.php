<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\TopicPartitions;
use Protocol\Kafka\Scheme;

/**
 * This API describes the valid offset range available for a set of topic-partitions.
 *
 * As with the produce and fetch APIs requests must be directed to the broker that is currently the leader for the
 * partitions in question. This can be determined using the metadata API.
 *
 * The response contains the starting offset of each segment for the requested partition as well as the "log end
 * offset" i.e. the offset of the next message that would be appended to the given partition.
 *
 * Since v2 if no topics (null input for list of topics) are provided, the offset information of all topics (or topic
 * partitions) associated with the group is returned
 *
 * OffsetFetch Request (Version: 2) => group_id [topics]
 *   group_id => STRING
 *   topics => topic [partitions]
 *     topic => STRING
 *     partitions => partition
 *       partition => INT32
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
    protected $consumerGroup;

    /**
     * @var TopicPartitions[]|null
     */
    protected $topicPartitions;

    /**
     * OffsetFetchRequest constructor.
     *
     * @param string            $consumerGroup   Name of the consumer group
     * @param TopicPartitions[] $topicPartitions List of topic => partitions to fetch
     * @param string            $clientId        Unique client identifier
     * @param int               $correlationId   Correlated request ID
     */
    public function __construct($consumerGroup, array $topicPartitions = null, $clientId = '', $correlationId = 0)
    {
        $this->consumerGroup   = $consumerGroup;
        $this->topicPartitions = $topicPartitions;

        parent::__construct(Kafka::OFFSET_FETCH, $clientId, $correlationId);
    }

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup'   => Scheme::TYPE_STRING,
            'topicPartitions' => ['topic' => TopicPartitions::class, Scheme::FLAG_NULLABLE => true]
        ];
    }
}
