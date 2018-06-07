<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

/**
 * Produce response DTO
 */
class ProduceResponsePartition
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
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $partition = new static();
        list(
            $partition->partition,
            $partition->errorCode,
            $partition->baseOffset,
            $partition->logAppendTime
        ) = array_values($stream->read('Npartition/nerrorCode/JbaseOffset/JlogAppendTime'));

        return $partition;
    }
}
