<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Topic metadata DTO
 */
class TopicMetadata implements BinarySchemeInterface
{
    use RestorableTrait;

    /**
     * The error code for the given topic.
     *
     * @var integer
     */
    public $topicErrorCode;

    /**
     * The name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Indicates if the topic is considered a Kafka internal topic
     *
     * @var boolean
     * @since Version 1 of protocol
     */
    public $isInternal;

    /**
     * Metadata for each partition of the topic.
     *
     * @var PartitionMetadata[]|array
     */
    public $partitions = [];

    public static function getScheme(): array
    {
        return [
            'topicErrorCode' => Scheme::TYPE_INT16,
            'topic'          => Scheme::TYPE_STRING,
            'isInternal'     => Scheme::TYPE_INT8,
            'partitions'     => [PartitionMetadata::class]
        ];
    }
}
