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


namespace Alpari\Kafka\Producer;

use Alpari\Kafka\Common\Cluster;

/**
 * The default partitioning strategy:
 *
 * 1) If a partition is specified in the record, use it
 * 2) If no partition is specified but a key is present choose a partition based on a hash of the key
 * 3) If no partition or key is present choose a partition in a round-robin fashion
 */
class DefaultPartitioner implements PartitionerInterface
{
    /**
     * @var int
     */
    private static $counter;

    /**
     * Compute the partition for the given record.
     *
     * @param string      $topic   The topic name
     * @param string|null $key     The key to partition on (or null if no key)
     * @param string|null $value   The value to partition on or null
     * @param Cluster     $cluster The current cluster metadata
     *
     * @return integer
     */
    public function partition(string $topic, ?string $key, ?string $value, Cluster $cluster): int
    {
        $partitions      = $cluster->partitionsForTopic($topic);
        $totalPartitions = count($partitions);

        if (isset($key)) {
            $partitionIndex = crc32($key) % $totalPartitions;
        } else {
            if (!isset(self::$counter)) {
                self::$counter = (integer) (microtime(true) * 1e6);
            }
            $partitionIndex = (self::$counter++) % $totalPartitions;
        }

        return $partitionIndex;
    }
}
