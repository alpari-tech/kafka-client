<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * DescribeGroups Request
 *
 * This API can be used to describe the current groups managed by a broker. To get a list of all groups in the cluster, you
 * must send DescribeGroups to all brokers.
 */
class DescribeGroupsRequest extends AbstractRequest
{
    /**
     * List of groups to describe
     *
     * @var array
     */
    private $groups;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $groups, $correlationId = 0, $clientId = '')
    {
        $this->groups = $groups;
        parent::__construct(Kafka::DESCRIBE_GROUPS, $correlationId, $clientId, Kafka::VERSION_0);
    }

    /**
     * @inheritDoc
     * DescribeGroupsRequest => [GroupId]
     *   GroupId => string
     */
    protected function packPayload()
    {
        $payload      = parent::packPayload();
        $payload .= pack('N', count($this->groups));

        foreach ($this->groups as $group) {
            $groupLength = strlen($group);
            $payload .= pack("na{$groupLength}", $groupLength, $group);
        }

        return $payload;
    }
}
