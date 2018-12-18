<?php
/**
 * @author Alexander.Lisachenko
 * @date   28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Consumer\MemberAssignment;
use Protocol\Kafka\DTO\SyncGroupRequestMember;
use Protocol\Kafka\Scheme;

/**
 * SyncGroup Request
 *
 * The sync group request is used by the group leader to assign state (e.g. partition assignments) to all members of
 * the current generation. All members send SyncGroup immediately after joining the group, but only the leader provides
 * the group's assignment.
 *
 * SyncGroupRequest => GroupId GenerationId MemberId GroupAssignment
 *   GroupId => string
 *   GenerationId => int32
 *   MemberId => string
 *   GroupAssignment => [MemberId MemberAssignment]
 *     MemberId => string
 *     MemberAssignment => bytes
 */
class SyncGroupRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    const VERSION = 1;

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

    /**
     * SyncGroupRequest constructor.
     *
     * @param string             $consumerGroup    The consumer group id
     * @param int                $generationId     The generation of the group
     * @param string|null        $memberId         The member id assigned by the group coordinator
     * @param MemberAssignment[] $groupAssignments List of group member assignments
     * @param string             $clientId         Client identifier
     * @param int                $correlationId    Correlated request ID
     */
    public function __construct(
        $consumerGroup,
        $generationId,
        $memberId = null,
        array $groupAssignments = [],
        $clientId = '',
        $correlationId = 0
    ) {
        $this->consumerGroup    = $consumerGroup;
        $this->generationId     = $generationId;
        $this->memberId         = $memberId;
        $packedGroupAssignments = [];
        foreach ($groupAssignments as $memberId => $memberAssignment) {
            $packedGroupAssignments[$memberId] = new SyncGroupRequestMember($memberId, $memberAssignment);
        }
        $this->groupAssignments = $packedGroupAssignments;

        parent::__construct(Kafka::SYNC_GROUP, $clientId, $correlationId);
    }

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup'    => Scheme::TYPE_STRING,
            'generationId'     => Scheme::TYPE_INT32,
            'memberId'         => Scheme::TYPE_NULLABLE_STRING,
            'groupAssignments' => ['memberId' => SyncGroupRequestMember::class],
        ];
    }
}
