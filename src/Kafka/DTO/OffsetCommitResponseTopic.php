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
 * OffsetCommitResponseTopic DTO
 *
 * OffsetCommitResponseTopic => topic [partition_responses]
 *   topic => STRING
 *   partition_responses => partition error_code
 *     partition => INT32
 *     error_code => INT16
 */
class OffsetCommitResponseTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Result for offset committing by each topic-partition
     *
     * @var OffsetCommitResponsePartition[]
     */
    public $partitions;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetCommitResponsePartition::class],
        ];
    }
}
