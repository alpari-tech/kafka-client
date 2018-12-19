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

use Alpari\Kafka\DTO\OffsetFetchResponseTopic;
use Alpari\Kafka\Scheme;

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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'topics' => ['topic' => OffsetFetchResponseTopic::class],
            'errorCode' => Scheme::TYPE_INT16,
        ];
    }
}
