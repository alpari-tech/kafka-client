<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Scheme;

/**
 * DescribeGroups Request
 *
 * This API can be used to describe the current groups managed by a broker. To get a list of all groups in the cluster, you
 * must send DescribeGroups to all brokers.
 *
 * DescribeGroups Request (Version: 0) => [group_ids]
 *   group_ids => STRING
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
    public function __construct(array $groups, $clientId = '', $correlationId = 0)
    {
        $this->groups = $groups;
        parent::__construct(Kafka::DESCRIBE_GROUPS, $clientId, $correlationId);
    }

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'groups' => [Scheme::TYPE_STRING]
        ];
    }
}
