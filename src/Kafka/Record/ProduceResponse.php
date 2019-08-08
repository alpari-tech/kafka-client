<?php
/*
 * This file is part of the Alpari Kafka client.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);


namespace Alpari\Kafka\Record;

use Alpari\Kafka\DTO\ProduceResponseTopic;
use Alpari\Kafka\Scheme;

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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'topics'       => ['topic' => ProduceResponseTopic::class],
            'throttleTime' => Scheme::TYPE_INT32
        ];
    }
}
