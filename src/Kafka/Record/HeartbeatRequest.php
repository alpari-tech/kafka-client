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

    public function __construct(
        string $consumerGroup,
        int $generationId,
        string $memberId,
        string $clientId = '',
        int $correlationId = 0
    ) {
        $this->consumerGroup = $consumerGroup;
        $this->generationId  = $generationId;
        $this->memberId      = $memberId;

        parent::__construct(Kafka::HEARTBEAT, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup' => Scheme::TYPE_STRING,
            'generationId'  => Scheme::TYPE_INT32,
            'memberId'      => Scheme::TYPE_STRING
        ];
    }
}
