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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

/**
 * Fetch request topic DTO
 *
 * FetchResponseTopic => topic [partition_responses]
 *   topic => STRING
 *   partition_responses => partition_header record_set
 *     partition_header => partition error_code high_watermark last_stable_offset log_start_offset [aborted_transactions]
 *       partition => INT32
 *       error_code => INT16
 *       high_watermark => INT64
 *       last_stable_offset => INT64
 *       log_start_offset => INT64
 *       aborted_transactions => producer_id first_offset
 *         producer_id => INT64
 *         first_offset => INT64
 *   record_set => RECORDS
 */
class FetchResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic for fetching
     *
     * @var string
     */
    public $topic;

    /**
     * Details about fetching for each topic's partition
     *
     * @var FetchResponsePartition[]
     */
    public $partitions;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => FetchResponsePartition::class]
        ];
    }
}
