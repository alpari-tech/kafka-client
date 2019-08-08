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


namespace Alpari\Kafka\Common;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

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
     * @var integer[]
     */
    public $replicas = [];

    /**
     * The set of nodes that are in sync with the leader for this partition.
     *
     * @var integer[]
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
