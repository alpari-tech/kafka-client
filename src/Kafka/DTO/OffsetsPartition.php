<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * Offsets response DTO
 */
class OffsetsPartition
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
     * List of offsets in the partition
     *
     * @var integer[]|array
     */
    public $offsets;

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
            $offsetsNumber
        ) = array_values($stream->read('Npartition/nerrorCode/NoffsetsNumber'));

        $partition->offsets = array_values($stream->read("J{$offsetsNumber}metadata"));

        return $partition;
    }
}
