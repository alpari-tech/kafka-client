<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetCommitResponseTopic DTO
 *
 * OffsetCommitResponseTopic => topic [partition_responses]
 *   topic => STRING
 *   partition_responses => partition error_code
 *     partition => INT32
 *     error_code => INT16
 */
class OffsetCommitResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Result for offset committing by each topic-partition
     *
     * @var OffsetCommitResponsePartition[]
     */
    public $partitions;

    public static function getScheme()
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetCommitResponsePartition::class],
        ];
    }
}
