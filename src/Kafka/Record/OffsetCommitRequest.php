<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\OffsetCommitRequestTopic;
use Protocol\Kafka\Scheme;

/**
 * OffsetCommit
 *
 * This api saves out the consumer's position in the stream for one or more partitions. In the scala API this happens
 * when the consumer calls commit() or in the background if "autocommit" is enabled. This is the position the consumer
 * will pick up from if it crashes before its next commit().
 *
 * OffsetCommit Request (Version: 2) => group_id generation_id member_id retention_time [topics]
 *   group_id => STRING
 *   generation_id => INT32
 *   member_id => STRING
 *   retention_time => INT64
 *   topics => topic [partitions]
 *     topic => STRING
 *     partitions => partition offset metadata
 *       partition => INT32
 *       offset => INT64
 *       metadata => NULLABLE_STRING
 */
class OffsetCommitRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    const VERSION = 2;

    /**
     * Generation id for unsubscribed consumer
     */
    const DEFAULT_GENERATION_ID = -1;

    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    /**
     * The generation of the group.
     *
     * @var int
     * @since Version 1 of protocol
     */
    private $generationId;

    /**
     * The member id assigned by the group coordinator.
     *
     * @var string
     * @since Version 1 of protocol
     */
    private $memberName;

    /**
     * Time period in ms to retain the offset.
     *
     * @var int
     * @since Version 2 of protocol
     */
    private $retentionTime;

    /**
     * @var OffsetCommitRequestTopic[]
     */
    private $topicPartitions;

    public function __construct(
        $consumerGroup,
        $generationId,
        $memberName,
        $retentionTime,
        array $topicPartitions,
        $clientId = '',
        $correlationId = 0
    ) {

        $this->consumerGroup   = $consumerGroup;
        $this->generationId    = $generationId;
        $this->memberName      = $memberName;
        $this->retentionTime   = $retentionTime;
        $packedTopicPartitions = [];
        foreach ($topicPartitions as $topic => $partitions) {
            $packedTopicPartitions[$topic] = new OffsetCommitRequestTopic($topic, $partitions);
        }
        $this->topicPartitions = $packedTopicPartitions;

        parent::__construct(Kafka::OFFSET_COMMIT, $clientId, $correlationId);
    }

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup'   => Scheme::TYPE_STRING,
            'generationId'    => Scheme::TYPE_INT32,
            'memberName'      => Scheme::TYPE_STRING,
            'retentionTime'   => Scheme::TYPE_INT64,
            'topicPartitions' => ['topic' => OffsetCommitRequestTopic::class]
        ];
    }
}
