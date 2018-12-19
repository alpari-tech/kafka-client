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
use Alpari\Kafka\Scheme;

/**
 * LeaveGroup Request
 *
 * To explicitly leave a group, the client can send a leave group request. This is preferred over letting the session
 * timeout expire since it allows the group to rebalance faster, which for the consumer means that less time will
 * elapse before partitions can be reassigned to an active member.
 *
 * LeaveGroup Request (Version: 0) => group_id member_id
 *   group_id => STRING
 *   member_id => STRING
 */
class LeaveGroupRequest extends AbstractRequest
{
    /**
     * The consumer group id.
     */
    private $consumerGroup;

    /**
     * The member id assigned by the group coordinator.
     */
    private $memberId;

    public function __construct(string $consumerGroup, string $memberId, string $clientId = '', int $correlationId = 0)
    {
        $this->consumerGroup = $consumerGroup;
        $this->memberId      = $memberId;

        parent::__construct(Kafka::LEAVE_GROUP, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup' => Scheme::TYPE_STRING,
            'memberId'      => Scheme::TYPE_STRING
        ];
    }
}
