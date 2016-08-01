<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Producer;

use Protocol\Kafka\Common\Cluster;

/**
 * Partitioner Interface is responsible to compute a partition for the topic producer
 */
interface PartitionerInterface
{
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
    public function partition($topic, $key, $value, Cluster $cluster);
}
