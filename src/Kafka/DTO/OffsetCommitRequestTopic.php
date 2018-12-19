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
 * OffsetCommitRequestTopic DTO
 *
 * OffsetCommitRequestTopic => topic [partitions]
 *   topic => STRING
 *   partitions => partition offset metadata
 *     partition => INT32
 *     offset => INT64
 *     metadata => NULLABLE_STRING
 */
class OffsetCommitRequestTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     */
    public $topic;

    /**
     * Partitions to commit offset.
     *
     * @var OffsetCommitRequestPartition[]
     */
    public $partitions;

    public function __construct(string $topic, array $partitions)
    {
        $packedPartitions = [];
        $this->topic      = $topic;
        foreach ($partitions as $partition => $timestamp) {
            $packedPartitions[$partition] = new OffsetCommitRequestPartition($partition, $timestamp);
        }
        $this->partitions = $packedPartitions;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetCommitRequestPartition::class],
        ];
    }
}
