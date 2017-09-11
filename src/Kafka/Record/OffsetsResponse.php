<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\OffsetsPartition;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Produce response object
 */
class OffsetsResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var array|OffsetsPartition[]
     */
    public $topics = [];

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $numberOfTopics,
        ) = array_values($stream->read('NcorrelationId/NnumberOfTopics'));

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            $topicLength = $stream->read('ntopicLength')['topicLength'];
            list(
                $topicName,
                $numberOfPartitions
            ) = array_values($stream->read("a{$topicLength}/NnumberOfPartitions"));

            for ($partition = 0; $partition < $numberOfPartitions; $partition++) {
                $partitionMetadata = OffsetsPartition::unpack($stream);
                $self->topics[$topicName][$partitionMetadata->partition] = $partitionMetadata;
            }
        }

        return $self;
    }
}
