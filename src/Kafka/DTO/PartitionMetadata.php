<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * Partition metadata DTO
 */
class PartitionMetadata
{
    /**
     * The error code for the partition, if any.
     *
     * @var integer
     */
    public $partitionErrorCode;

    /**
     * The id of the partition.
     *
     * @var integer
     */
    public $partitionId;

    /**
     * The id of the broker acting as leader for this partition.
     *
     * @var integer
     */
    public $leader;

    /**
     * The set of all nodes that host this partition.
     *
     * @var array|integer[]
     */
    public $replicas = [];

    /**
     * The set of nodes that are in sync with the leader for this partition.
     *
     * @var array|integer[]
     */
    public $isr = [];

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $partitionMetadata = new static();
        list(
            $partitionMetadata->partitionErrorCode,
            $partitionMetadata->partitionId,
            $partitionMetadata->leader,
            $numberOfReplicas
        ) = array_values($stream->read('npartitionErrorCode/NpartitionId/Nleader/NnumberOfReplicas'));

        $partitionMetadata->replicas = array_values($stream->read("N{$numberOfReplicas}"));

        $numberOfIsr = $stream->read('NnumberOfIsr')['numberOfIsr'];
        $partitionMetadata->isr = array_values($stream->read("N{$numberOfIsr}"));

        return $partitionMetadata;
    }
}
