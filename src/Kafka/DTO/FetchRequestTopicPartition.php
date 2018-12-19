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
 * Class FetchRequestTopicPartition
 *
 * FetchRequestTopicPartition => partition fetch_offset log_start_offset max_bytes
 *   partition => INT32
 *   fetch_offset => INT64
 *   log_start_offset => INT64 (Since FetchRequest v5)
 *   max_bytes => INT32
 */
class FetchRequestTopicPartition implements BinarySchemeInterface
{
    /**
     * Topic partition id
     */
    public $partition;

    /**
     * Message offset.
     */
    public $fetchOffset;

    /**
     * Earliest available offset of the follower replica.
     *
     * The field is only used when request is sent by follower.
     *
     * @since 0.11.0.0 Kafka
     */
    public $logStartOffset;

    /**
     * Maximum bytes to fetch.
     */
    public $maxBytes;

    public function __construct(int $partition, int $fetchOffset, int $maxBytes, int $logStartOffset = -1)
    {
        $this->partition      = $partition;
        $this->fetchOffset    = $fetchOffset;
        $this->logStartOffset = $logStartOffset;
        $this->maxBytes       = $maxBytes;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition'      => Scheme::TYPE_INT32,
            'fetchOffset'    => Scheme::TYPE_INT64,
            'logStartOffset' => Scheme::TYPE_INT64,
            'maxBytes'       => Scheme::TYPE_INT32
        ];
    }
}
