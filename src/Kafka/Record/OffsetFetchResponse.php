<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\OffsetFetchResponsePartition;
use Protocol\Kafka\DTO\OffsetFetchResponseTopic;
use Protocol\Kafka\Record;
use Protocol\Kafka\Scheme;
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
class OffsetFetchResponse extends AbstractResponse implements BinarySchemeInterface
{
    /**
     * List of topic responses
     *
     * @var OffsetFetchResponseTopic[]
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

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'topics' => ['topic' => OffsetFetchResponseTopic::class],
            'errorCode' => Scheme::TYPE_INT16,
        ];
    }
}
