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
use Alpari\Kafka\DTO\JoinGroupResponseMember;
use Alpari\Kafka\Scheme;
use Alpari\Kafka\Stream\StringStream;

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
     * @param Cluster                   $metadata      Current topic/broker metadata known by consumer
     * @param JoinGroupResponseMember[] $subscriptions Subscriptions from all members
     *
     * @return array|MemberAssignment[] A map from the members to their respective assignment. This should have one entry
     *         for all members who in the input subscription map.
     */
    public function assign(Cluster $metadata, array $subscriptions): array
    {
        $topicMembers         = [];
        $partitionAssignments = [];

        foreach ($subscriptions as $memberId => $subscriptionData) {
            $stringMetadata       = new StringStream($subscriptionData->metadata);
            $subscriptionMetadata = Scheme::readObjectFromStream(Subscription::class, $stringMetadata);
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
            $result[$memberId] = new MemberAssignment($partitionAssignment);
        }
        $unassignedMembers = array_diff_key($subscriptions, $result);
        foreach (array_keys($unassignedMembers) as $unassignedMemberId) {
            $result[$unassignedMemberId] = new MemberAssignment();
        }

        return $result;
    }
}
