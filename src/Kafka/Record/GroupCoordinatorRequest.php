<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

/**
 * The offsets for a given consumer group are maintained by a specific broker called the group coordinator. i.e., a
 * consumer needs to issue its offset commit and fetch requests to this specific broker.
 *
 * It can discover the current coordinator by issuing a group coordinator request.
 */
class GroupCoordinatorRequest extends AbstractRequest
{
    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    public function __construct($consumerGroup, $clientId = '', $correlationId = 0)
    {
        $this->consumerGroup   = $consumerGroup;

        parent::__construct(Kafka::GROUP_COORDINATOR, $clientId, $correlationId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload     = parent::packPayload();
        $groupLength = strlen($this->consumerGroup);

        $payload .= pack("na{$groupLength}", $groupLength, $this->consumerGroup);

        return $payload;
    }
}
