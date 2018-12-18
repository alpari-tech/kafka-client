<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetsResponseTopic DTO
 *
 * OffsetsResponseTopic => topic [partition_responses]
 *   topic => STRING
 *   partition_responses => partition error_code timestamp offset
 *     partition => INT32
 *     error_code => INT16
 *     timestamp => INT64
 *     offset => INT64
 */
class OffsetsResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Partition responses.
     *
     * @var OffsetsResponsePartition[]
     */
    public $partitions;

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetsResponsePartition::class],
        ];
    }
}
