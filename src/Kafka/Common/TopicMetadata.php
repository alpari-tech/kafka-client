<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka\Stream;

/**
 * Topic metadata DTO
 */
class TopicMetadata
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
     * Metadata for each partition of the topic.
     *
     * @var PartitionMetadata[]|array
     */
    public $partitions = [];

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $topic = new static();
        list($topic->topicErrorCode, $topicLength) = array_values($stream->read('ntopicErrorCode/ntopicLength'));
        list(
            $topic->topic,
            $numberOfPartitions
        ) = array_values($stream->read("a{$topicLength}topic/NnumberOfPartition"));

        for ($partition = 0; $partition < $numberOfPartitions; $partition++) {
            $partitionMetadata = PartitionMetadata::unpack($stream);

            $topic->partitions[$partitionMetadata->partitionId] = $partitionMetadata;
        }

        return $topic;
    }
}
