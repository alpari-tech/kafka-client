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
 * OffsetsResponseTopic DTO
 *
 * OffsetsResponseTopic => topic [partition_responses]
 *   topic => STRING
 *   partition_responses => partition error_code timestamp offset
 *     partition => INT32
 *     error_code => INT16
 *     timestamp => INT64
 *     offset => INT64
 */
class OffsetsResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Partition responses.
     *
     * @var OffsetsResponsePartition[]
     */
    public $partitions;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetsResponsePartition::class],
        ];
    }
}
