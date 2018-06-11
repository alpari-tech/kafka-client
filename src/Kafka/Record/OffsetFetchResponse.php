<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\OffsetFetchPartition;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * OffsetFetch response object
 *
 * OffsetFetch Response (Version: 2) => [responses] error_code
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition offset metadata error_code
 *     partition => INT32
 *     offset => INT64
 *     metadata => NULLABLE_STRING
 *     error_code => INT16
 *   error_code => INT16
 */
class OffsetFetchResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var array|OffsetFetchPartition[]
     */
    public $topics = [];

    /**
     * Error code returned by the coordinator
     *
     * @since Version 2 of protocol
     *
     * @var integer
     */
    public $errorCode;

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
                $topicMetadata = OffsetFetchPartition::unpack($stream);
                $self->topics[$topicName][$topicMetadata->partition] = $topicMetadata;
            }
        }
        $self->errorCode = $stream->read('nerrorCode')['errorCode'];

        return $self;
    }
}
