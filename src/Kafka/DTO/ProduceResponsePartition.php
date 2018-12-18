<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Produce response partition DTO
 *
 * ProduceResponsePartition => partition error_code base_offset log_append_time
 *   partition => INT32
 *   error_code => INT16
 *   base_offset => INT64
 *   log_append_time => INT64
 */
class ProduceResponsePartition implements BinarySchemeInterface
{
    /**
     * The partition this response entry corresponds to.
     *
     * @var integer
     */
    public $partition;

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
     * The offset assigned to the first message in the message set appended to this partition.
     *
     * @var integer
     */
    public $baseOffset;

    /**
     * If LogAppendTime is used for the topic, this is the timestamp assigned by the broker to the message set.
     * All the messages in the message set have the same timestamp.
     *
     * If CreateTime is used, this field is always -1. The producer can assume the timestamp of the messages in the
     * produce request has been accepted by the broker if there is no error code returned.
     *
     * Unit is milliseconds since beginning of the epoch (midnight Jan 1, 1970 (UTC)).
     *
     * @var integer
     * @since Version 2 of protocol
     */
    public $logAppendTime;

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme(): array
    {
        return [
            'partition'     => Scheme::TYPE_INT32,
            'errorCode'     => Scheme::TYPE_INT16,
            'baseOffset'    => Scheme::TYPE_INT64,
            'logAppendTime' => Scheme::TYPE_INT64
        ];
    }
}
