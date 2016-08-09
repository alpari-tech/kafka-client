<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Stream\StringStream;

/**
 * The roundrobin assignor lays out all the available partitions and all the available consumers.
 *
 * It then proceeds to do a roundrobin assignment from partition to consumer. If the subscriptions of all consumer
 * instances are identical, then the partitions will be uniformly distributed. (i.e., the partition ownership counts
 * will be within a delta of exactly one across all consumers.)
 *
 * For example, suppose there are two consumers C0 and C1, two topics t0 and t1, and each topic has 3 partitions,
 * resulting in partitions t0p0, t0p1, t0p2, t1p0, t1p1, and t1p2.
 *
 * The assignment will be:
 *
 * C0: [t0p0, t0p2, t1p1]
 * C1: [t0p1, t1p0, t1p2]
 */
class RoundRobinAssignor implements PartitionAssignorInterface
{
    /**
     * Perform the group assignment given the member subscriptions and current cluster metadata.
     *
     * @param Cluster $metadata Current topic/broker metadata known by consumer
     * @param array $subscriptions Subscriptions from all members
     *
     * @return array|MemberAssignment[] A map from the members to their respective assignment. This should have one entry
     *         for all members who in the input subscription map.
     */
    public function assign(Cluster $metadata, array $subscriptions)
    {
        $topicMembers         = [];
        $partitionAssignments = [];

        foreach ($subscriptions as $memberId => $subscriptionData) {
            $subscriptionMetadata = Subscription::unpack(new StringStream($subscriptionData));
            foreach ($subscriptionMetadata->topics as $topic) {
                $topicMembers[$topic][] = $memberId;
            }
        }

        $requestedTopics = array_keys($topicMembers);
        foreach ($requestedTopics as $requestedTopic) {
            $partitions     = $metadata->partitionsForTopic($requestedTopic);
            $totalConsumers = count($topicMembers[$requestedTopic]);
            foreach ($partitions as $index=>$partition) {
                $memberIndex = $index % $totalConsumers;
                $memberId    = $topicMembers[$requestedTopic][$memberIndex];
                $partitionAssignments[$memberId][$requestedTopic][$partition->partitionId] = $partition->partitionId;
            }
        }

        $result = [];
        foreach ($partitionAssignments as $memberId => $partitionAssignment) {
            $result[$memberId] = MemberAssignment::fromTopicPartitions($partitionAssignment);
        }

        return $result;
    }
}
