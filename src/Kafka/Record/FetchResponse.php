<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\FetchResponseTopic;
use Protocol\Kafka\Scheme;

/**
 * Fetch response object
 *
 * Fetch Response (Version: 5) => throttle_time_ms [responses]
 *   throttle_time_ms => INT32
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition_header record_set
 *       partition_header => partition error_code high_watermark last_stable_offset log_start_offset [aborted_transactions]
 *         partition => INT32
 *         error_code => INT16
 *         high_watermark => INT64
 *         last_stable_offset => INT64
 *         log_start_offset => INT64
 *         aborted_transactions => producer_id first_offset
 *           producer_id => INT64
 *           first_offset => INT64
 *     record_set => RECORDS
 */
class FetchResponse extends AbstractResponse implements BinarySchemeInterface
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
     * @var FetchResponseTopic[]
     */
    public $topics = [];

    public static function getScheme()
    {
        return parent::getScheme() + [
            'throttleTimeMs' => Scheme::TYPE_INT32,
            'topics'         => ['topic' => FetchResponseTopic::class],
        ];
    }
}
