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

/**
 * ListGroups Request
 *
 * This API can be used to find the current groups managed by a broker. To get a list of all groups in the cluster, you
 * must send ListGroup to all brokers.
 */
class ListGroupsRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $clientId = '', int $correlationId = 0)
    {
        parent::__construct(Kafka::LIST_GROUPS, $clientId, $correlationId);
    }
}
