<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * Heartbeat Request
 *
 * Once a member has joined and synced, it will begin sending periodic heartbeats to keep itself in the group. If not
 * heartbeat has been received by the coordinator with the configured session timeout, the member will be kicked out of
 * the group.
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

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload      = parent::packPayload();
        $groupLength  = strlen($this->consumerGroup);
        $memberLength = strlen($this->memberId);

        $payload .= pack(
            "na{$groupLength}Nna{$memberLength}",
            $groupLength,
            $this->consumerGroup,
            $this->generationId,
            $memberLength,
            $this->memberId
        );

        return $payload;
    }
}
