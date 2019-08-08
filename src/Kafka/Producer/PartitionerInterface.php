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
     * @return int
     */
    public function partition(string $topic, ?string $key, ?string $value, Cluster $cluster): int;
}
