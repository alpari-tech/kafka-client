<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * OffsetFetch/OffsetCommit DTO
 */
class OffsetFetchPartition
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
            $partition->offset,
            $metadataLength
        ) = array_values($stream->read('Npartition/Joffset/nmetadataLength'));
        $metadataLength = $metadataLength < 0x8000 ? $metadataLength : 0;
        list(
            $partition->metadata,
            $partition->errorCode
        ) = array_values($stream->read("a{$metadataLength}metadata/nerrorCode"));

        return $partition;
    }

    public function __toString()
    {
        $metadataLength = strlen($this->metadata);
        $payload        = pack(
            "NJna{$metadataLength}",
            $this->partition,
            $this->offset,
            $metadataLength ? $metadataLength : -1,
            $this->metadata
        );

        return $payload;
    }

    public static function fromPartitionOffset($partition, $offset, $metadata = null)
    {
        $instance = new static;
        $instance->partition = $partition;
        $instance->offset    = $offset;
        $instance->metadata  = $metadata;

        return $instance;
    }
}
