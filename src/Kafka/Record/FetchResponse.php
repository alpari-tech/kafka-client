<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\FetchResponsePartition;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Fetch response object
 *
 * Fetch Response (Version: 4) => throttle_time_ms [responses]
 *   throttle_time_ms => INT32
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition_header record_set
 *       partition_header => partition error_code high_watermark last_stable_offset [aborted_transactions]
 *         partition => INT32
 *         error_code => INT16
 *         high_watermark => INT64
 *         last_stable_offset => INT64
 *         aborted_transactions => producer_id first_offset
 *           producer_id => INT64
 *           first_offset => INT64
 *     record_set => RECORDS
 */
class FetchResponse extends AbstractResponse
{

    /**
     * Duration in milliseconds for which the request was throttled due to quota violation.
     * (Zero if the request did not violate any quota.)
     *
     * @var integer
     * @since Version 1 of protocol
     */
    public $throttleTimeMs;

    /**
     * List of fetch responses
     *
     * @var array|FetchResponsePartition[]
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
            $self->throttleTimeMs,
            $numberOfTopics,
        ) = array_values($stream->read('NcorrelationId/NthrottleTimeMs/NnumberOfTopics'));

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            $topicLength = $stream->read('ntopicLength')['topicLength'];
            list(
                $topicName,
                $numberOfPartitions
            ) = array_values($stream->read("a{$topicLength}/NnumberOfPartitions"));

            for ($partition = 0; $partition < $numberOfPartitions; $partition++) {
                $topicMetadata = FetchResponsePartition::unpack($stream);
                $self->topics[$topicName][$topicMetadata->partition] = $topicMetadata;
            }
        }

        return $self;
    }
}
