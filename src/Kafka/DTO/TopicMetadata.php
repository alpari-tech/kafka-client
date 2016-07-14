<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;

/**
 * Topic metadata DTO
 */
class TopicMetadata
{
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
     * Metadata for each partition of the topic.
     *
     * @var PartitionMetadata[]|array
     */
    public $partitions = [];
}
