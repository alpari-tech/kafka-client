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


namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetsRequestTopic DTO
 *
 * OffsetsRequestTopic => topic [partitions]
 *   topic => STRING
 *   partitions => partition timestamp
 *     partition => INT32
 *     timestamp => INT64
 */
class OffsetsRequestTopic implements BinarySchemeInterface
{
    /**
     * Name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Partitions to list offset.
     *
     * @var OffsetsRequestPartition[]
     */
    public $partitions;

    /**
     * @inheritDoc
     */
    public function __construct($topic, array $partitions)
    {
        $packedPartitions = [];
        $this->topic      = $topic;
        foreach ($partitions as $partition => $timestamp) {
            $packedPartitions[$partition] = new OffsetsRequestPartition($partition, $timestamp);
        }
        $this->partitions = $packedPartitions;
    }

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => OffsetsRequestPartition::class],
        ];
    }
}
