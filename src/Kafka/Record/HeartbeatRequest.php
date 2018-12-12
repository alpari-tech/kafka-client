<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Scheme;

/**
 * Heartbeat Request
 *
 * Once a member has joined and synced, it will begin sending periodic heartbeats to keep itself in the group. If not
 * heartbeat has been received by the coordinator with the configured session timeout, the member will be kicked out of
 * the group.
 *
 * Heartbeat Request (Version: 0) => group_id generation_id member_id
 *   group_id => STRING
 *   generation_id => INT32
 *   member_id => STRING
 */
class HeartbeatRequest extends AbstractRequest
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


    public function __construct($consumerGroup, $generationId, $memberId, $clientId = '', $correlationId = 0)
    {
        $this->consumerGroup = $consumerGroup;
        $this->generationId  = $generationId;
        $this->memberId      = $memberId;

        parent::__construct(Kafka::HEARTBEAT, $clientId, $correlationId);
    }

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup' => Scheme::TYPE_STRING,
            'generationId'  => Scheme::TYPE_INT32,
            'memberId'      => Scheme::TYPE_STRING
        ];
    }
}
