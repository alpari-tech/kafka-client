<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetFetch/OffsetCommit DTO
 */
class OffsetFetchResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Information about each offset for the partition in the topic
     *
     * @var OffsetFetchResponsePartition[]
     */
    public $partitions;


    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetFetchResponsePartition::class],
        ];
    }
}
