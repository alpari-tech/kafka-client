<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Offset commit response object
 */
class OffsetCommitResponse extends AbstractResponse
{
    /**
     * List of topics with partition result
     *
     * @var array
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
                list ($partitionId, $partitionErrorCode) = array_values($stream->read('Npartition/nErrorCode'));
                $self->topics[$topicName][$partitionId] = $partitionErrorCode;
            }
        }

        return $self;
    }
}
