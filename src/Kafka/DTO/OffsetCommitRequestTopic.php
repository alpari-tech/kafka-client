<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetCommitRequestTopic DTO
 *
 * OffsetCommitRequestTopic => topic [partitions]
 *   topic => STRING
 *   partitions => partition offset metadata
 *     partition => INT32
 *     offset => INT64
 *     metadata => NULLABLE_STRING
 */
class OffsetCommitRequestTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Partitions to commit offset.
     *
     * @var OffsetCommitRequestPartition[]
     */
    public $partitions;

    /**
     * @inheritDoc
     */
    public function __construct($topic, array $partitions)
    {
        $packedPartitions = [];
        $this->topic      = $topic;
        foreach ($partitions as $partition => $timestamp) {
            $packedPartitions[$partition] = new OffsetCommitRequestPartition($partition, $timestamp);
        }
        $this->partitions = $packedPartitions;
    }

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetCommitRequestPartition::class],
        ];
    }
}
