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


namespace Alpari\Kafka\Record;

use Alpari\Kafka;
use Alpari\Kafka\Consumer\MemberAssignment;
use Alpari\Kafka\DTO\SyncGroupRequestMember;
use Alpari\Kafka\Scheme;

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
    protected const VERSION = 1;

    /**
     * The consumer group id.
     */
    private $consumerGroup;

    /**
     * The generation of the group.
     */
    private $generationId;

    /**
     * The member id assigned by the group coordinator.
     */
    private $memberId;

    /**
     * List of group member assignments
     *
     * @var SyncGroupRequestMember[]
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
        string $consumerGroup,
        int $generationId,
        ?string $memberId = null,
        array $groupAssignments = [],
        string $clientId = '',
        int $correlationId = 0
    ) {
        $this->consumerGroup    = $consumerGroup;
        $this->generationId     = $generationId;
        $this->memberId         = $memberId;
        $packedGroupAssignments = [];
        foreach ($groupAssignments as $groupMemberId => $memberAssignment) {
            $packedGroupAssignments[$groupMemberId] = new SyncGroupRequestMember($groupMemberId, $memberAssignment);
        }
        $this->groupAssignments = $packedGroupAssignments;

        parent::__construct(Kafka::SYNC_GROUP, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
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
