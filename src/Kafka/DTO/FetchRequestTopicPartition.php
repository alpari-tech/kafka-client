<?php
/**
 * Copyright
 *
 * @author Alexander.Lisachenko
 * @date   17.07.2018
 */

namespace Protocol\Kafka\DTO;


use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

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
     *
     * @var integer
     */
    public $partition;

    /**
     * Message offset.
     *
     * @var integer
     */
    public $fetchOffset;

    /**
     * Earliest available offset of the follower replica.
     *
     * The field is only used when request is sent by follower.
     *
     * @since 0.11.0.0 Kafka
     * @var integer
     */
    public $logStartOffset;

    /**
     * Maximum bytes to fetch.
     *
     * @var integer
     */
    public $maxBytes;

    public function __construct($partition, $fetchOffset, $maxBytes, $logStartOffset = -1)
    {
        $this->partition      = $partition;
        $this->fetchOffset    = $fetchOffset;
        $this->logStartOffset = $logStartOffset;
        $this->maxBytes       = $maxBytes;
    }

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
