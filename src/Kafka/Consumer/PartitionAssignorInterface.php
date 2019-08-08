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

namespace Alpari\Kafka\Consumer;

use Alpari\Kafka\Common\Cluster;

/**
 * This interface is used to define custom partition assignment for use in KafkaConsumer.
 *
 * Members of the consumer group subscribe to the topics they are interested in and forward their subscriptions to a
 * Kafka broker serving as the group coordinator. The coordinator selects one member to perform the group assignment
 * and propagates the subscriptions of all members to it. Then assign() is called to perform the
 * assignment and the results are forwarded back to each respective members
 *
 * In some cases, it is useful to forward additional metadata to the assignor in order to make
 * assignment decisions. For this, you can override subscription() and provide custom
 * userData in the returned Subscription. For example, to have a rack-aware assignor, an implementation
 * can use this user data to forward the rackId belonging to each member.
 */
interface PartitionAssignorInterface
{
    /**
     * Perform the group assignment given the member subscriptions and current cluster metadata.
     *
     * @param Cluster $metadata      Current topic/broker metadata known by consumer
     * @param array   $subscriptions Subscriptions from all members
     *
     * @return array|MemberAssignment[] A map from the members to their respective assignment.
     *                                  This should have one entry for all members who in the input subscription map.
     */
    public function assign(Cluster $metadata, array $subscriptions): array;
}
