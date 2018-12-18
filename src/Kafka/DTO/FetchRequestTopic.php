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
 * Fetch request topic DTO
 *
 * FetchRequestTopic => topic [partitions]
 *   topic => STRING
 *   partitions => partition fetch_offset max_bytes
 *     partition => INT32
 *     fetch_offset => INT64
 *     max_bytes => INT32
 */
class FetchRequestTopic implements BinarySchemeInterface
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
     * @var FetchRequestTopicPartition[]
     */
    public $partitions;

    /**
     * @inheritDoc
     */
    public function __construct($topic, array $partitions = [])
    {
        $this->topic      = $topic;
        $this->partitions = $partitions;
    }

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => FetchRequestTopicPartition::class]
        ];
    }
}
