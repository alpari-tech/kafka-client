<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\ProduceResponseTopic;
use Protocol\Kafka\Scheme;

/**
 * Produce response object
 *
 * Produce Response (Version: 3) => [responses] throttle_time_ms
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition error_code base_offset log_append_time
 *       partition => INT32
 *       error_code => INT16
 *       base_offset => INT64
 *       log_append_time => INT64
 *   throttle_time_ms => INT32
 */
class ProduceResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var ProduceResponseTopic[]
     */
    public $topics;

    /**
     * Duration in milliseconds for which the request was throttled due to quota violation. (Zero if the request did not violate any quota).
     *
     * @var integer
     * @since Version 1 of protocol
     */
    public $throttleTime;

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'topics'       => ['topic' => ProduceResponseTopic::class],
            'throttleTime' => Scheme::TYPE_INT32
        ];
    }
}
