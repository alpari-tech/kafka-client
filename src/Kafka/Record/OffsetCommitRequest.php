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


namespace Alpari\Kafka\Record;

use Alpari\Kafka;
use Alpari\Kafka\DTO\OffsetCommitRequestTopic;
use Alpari\Kafka\Scheme;

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
     * Generation id for unsubscribed consumer
     */
    public const DEFAULT_GENERATION_ID = -1;

    /**
     * @inheritDoc
     */
    protected const VERSION = 2;

    /**
     * The consumer group id.
     */
    private $consumerGroup;

    /**
     * The generation of the group.
     *
     * @since Version 1 of protocol
     */
    private $generationId;

    /**
     * The member id assigned by the group coordinator.
     *
     * @since Version 1 of protocol
     */
    private $memberName;

    /**
     * Time period in ms to retain the offset.
     *
     * @since Version 2 of protocol
     */
    private $retentionTime;

    /**
     * @var OffsetCommitRequestTopic[]
     */
    private $topicPartitions;

    public function __construct(
        string $consumerGroup,
        int $generationId,
        string $memberName,
        int $retentionTime,
        array $topicPartitions,
        string $clientId = '',
        int $correlationId = 0
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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
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
