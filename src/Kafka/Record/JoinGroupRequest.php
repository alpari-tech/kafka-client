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
use Alpari\Kafka\DTO\JoinGroupRequestProtocol;
use Alpari\Kafka\Scheme;

/**
 * Join Group Request
 *
 * The join group request is used by a client to become a member of a group. When new members join an existing group,
 * all previous members are required to rejoin by sending a new join group request. When a member first joins the
 * group, the memberId will be empty (i.e. ""), but a rejoining member should use the same memberId from the previous
 * generation.
 */
class JoinGroupRequest extends AbstractRequest
{
    /**
     * Member id for self-assigned consumer
     */
    public const DEFAULT_MEMBER_ID = '';

    /**
     * @inheritDoc
     */
    protected const VERSION = 1;

    /**
     * The consumer group id.
     */
    private $consumerGroup;

    /**
     * The coordinator considers the consumer dead if it receives no heartbeat after this timeout in ms.
     */
    private $sessionTimeout;

    /**
     * The maximum time that the coordinator will wait for each member to rejoin when rebalancing the group
     */
    private $rebalanceTimeout;

    /**
     * The member id assigned by the group coordinator.
     */
    private $memberId;

    /**
     * Unique name for class of protocols implemented by group
     */
    private $protocolType;

    /**
     * List of protocols that the member supports as key=>value pairs, where value is metadata
     *
     * @var array
     */
    private $groupProtocols;

    public function __construct(
        string $consumerGroup,
        int $sessionTimeout,
        int $rebalanceTimeout,
        string $memberId,
        string $protocolType,
        array $groupProtocols,
        string $clientId = '',
        int $correlationId = 0
    ) {
        $this->consumerGroup    = $consumerGroup;
        $this->sessionTimeout   = $sessionTimeout;
        $this->rebalanceTimeout = $rebalanceTimeout;
        $this->memberId         = $memberId;
        $this->protocolType     = $protocolType;
        $packedProtocols        = [];
        foreach ($groupProtocols as $protocolName => $protocolMetadata) {
            $packedProtocols[$protocolName] = new JoinGroupRequestProtocol($protocolName, $protocolMetadata);
        }

        $this->groupProtocols = $packedProtocols;

        parent::__construct(Kafka::JOIN_GROUP, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'consumerGroup'    => Scheme::TYPE_STRING,
            'sessionTimeout'   => Scheme::TYPE_INT32,
            'rebalanceTimeout' => Scheme::TYPE_INT32,
            'memberId'         => Scheme::TYPE_STRING,
            'protocolType'     => Scheme::TYPE_STRING,
            'groupProtocols'   => ['protocolName' => JoinGroupRequestProtocol::class]
        ];
    }
}
