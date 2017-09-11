<?php
/**
 * @author Alexander.Lisachenko
 * @date   28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

/**
 * SyncGroup Request
 *
 * The sync group request is used by the group leader to assign state (e.g. partition assignments) to all members of
 * the current generation. All members send SyncGroup immediately after joining the group, but only the leader provides
 * the group's assignment.
 */
class SyncGroupRequest extends AbstractRequest
{
    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    /**
     * The generation of the group.
     *
     * @var int
     */
    private $generationId;

    /**
     * The member id assigned by the group coordinator.
     *
     * @var string
     */
    private $memberId;

    /**
     * List of group member assignments
     *
     * @var array
     */
    private $groupAssignments;


    public function __construct(
        $consumerGroup,
        $generationId,
        $memberId,
        array $groupAssignments = [],
        $clientId = '',
        $correlationId = 0
    ) {
        $this->consumerGroup    = $consumerGroup;
        $this->generationId     = $generationId;
        $this->memberId         = $memberId;
        $this->groupAssignments = $groupAssignments;

        parent::__construct(Kafka::SYNC_GROUP, $clientId, $correlationId, Kafka::VERSION_0);
    }

    /**
     * @inheritDoc
     *
     * SyncGroupRequest => GroupId GenerationId MemberId GroupAssignment
     *   GroupId => string
     *   GenerationId => int32
     *   MemberId => string
     *   GroupAssignment => [MemberId MemberAssignment]
     *     MemberId => string
     *     MemberAssignment => bytes

     */
    protected function packPayload()
    {
        $payload      = parent::packPayload();
        $groupLength  = strlen($this->consumerGroup);
        $memberLength = strlen($this->memberId);

        $payload .= pack(
            "na{$groupLength}Nna{$memberLength}N",
            $groupLength,
            $this->consumerGroup,
            $this->generationId,
            $memberLength,
            $this->memberId,
            count($this->groupAssignments)
        );

        foreach ($this->groupAssignments as $memberId => $memberAssignment) {
            $memberAssignment       = (string)$memberAssignment;
            $memberLength           = strlen($memberId);
            $memberAssignmentLength = strlen($memberAssignment);
            $payload .= pack(
                "na{$memberLength}N",
                $memberLength,
                $memberId,
                $memberAssignmentLength
            );
            $payload .= $memberAssignment;
        }

        return $payload;
    }
}
