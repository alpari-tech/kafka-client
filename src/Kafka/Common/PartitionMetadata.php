<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Information about a topic-partition metadata.
 */
class PartitionMetadata implements BinarySchemeInterface
{
    use RestorableTrait;

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

    public static function getScheme(): array
    {
        return [
            'partitionErrorCode' => Scheme::TYPE_INT16,
            'partitionId'        => Scheme::TYPE_INT32,
            'leader'             => Scheme::TYPE_INT32,
            'replicas'           => [Scheme::TYPE_INT32],
            'isr'                => [Scheme::TYPE_INT32]
        ];
    }
}
