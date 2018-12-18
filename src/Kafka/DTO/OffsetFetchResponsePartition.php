<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * OffsetFetchResponsePartition DTO
 *
 * OffsetFetchResponsePartition => partition offset metadata error_code
 *   partition => INT32
 *   offset => INT64
 *   metadata => NULLABLE_STRING
 *   error_code => INT16
 */
class OffsetFetchResponsePartition implements BinarySchemeInterface
{
    /**
     * The partition this response entry corresponds to.
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

    /**
     * The error from this partition, if any.
     *
     * Errors are given on a per-partition basis because a given partition may be unavailable or maintained on a
     * different host, while others may have successfully accepted the produce request.
     *
     * @var integer
     */
    public $errorCode;

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
            'metadata'  => Scheme::TYPE_NULLABLE_STRING,
            'errorCode' => Scheme::TYPE_INT16
        ];
    }
}
