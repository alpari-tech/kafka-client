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
 * OffsetCommitRequestPartition DTO
 *
 * OffsetCommitRequestPartition => partition offset metadata
 *   partition => INT32
 *   offset => INT64
 *   metadata => NULLABLE_STRING
 */
class OffsetCommitRequestPartition implements BinarySchemeInterface
{
    /**
     * The partition this request entry corresponds to.
     *
     * @var integer
     */
    public $partition;

    /**
     * The offset assigned to the first message in the message set appended to this partition.
     *
     * @var integer
     */
    public $offset;

    /**
     * Any associated metadata the client wants to keep.
     *
     * @var string
     */
    public $metadata;

    public function __construct($partition, $offset, $metadata = null)
    {
        $this->partition = $partition;
        $this->offset    = $offset;
        $this->metadata  = $metadata;
    }

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme(): array
    {
        return [
            'partition' => Scheme::TYPE_INT32,
            'offset'    => Scheme::TYPE_INT64,
            'metadata'  => Scheme::TYPE_NULLABLE_STRING
        ];
    }
}
