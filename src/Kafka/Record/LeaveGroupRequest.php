<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * LeaveGroup Request
 *
 * To explicitly leave a group, the client can send a leave group request. This is preferred over letting the session
 * timeout expire since it allows the group to rebalance faster, which for the consumer means that less time will
 * elapse before partitions can be reassigned to an active member.
 */
class LeaveGroupRequest extends AbstractRequest
{
    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    /**
     * The member id assigned by the group coordinator.
     *
     * @var string
     */
    private $memberId;

    public function __construct($consumerGroup, $memberId, $clientId = '', $correlationId = 0)
    {
        $this->consumerGroup = $consumerGroup;
        $this->memberId      = $memberId;

        parent::__construct(Kafka::LEAVE_GROUP, $clientId, $correlationId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload      = parent::packPayload();
        $groupLength  = strlen($this->consumerGroup);
        $memberLength = strlen($this->memberId);

        $payload .= pack(
            "na{$groupLength}na{$memberLength}", 
            $groupLength, 
            $this->consumerGroup, 
            $memberLength, 
            $this->memberId
        );

        return $payload;
    }
}
