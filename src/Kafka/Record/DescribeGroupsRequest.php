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
     */
    private $groups;

    public function __construct(array $groups, string $clientId = '', int $correlationId = 0)
    {
        $this->groups = $groups;
        parent::__construct(Kafka::DESCRIBE_GROUPS, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'groups' => [Scheme::TYPE_STRING]
        ];
    }
}
