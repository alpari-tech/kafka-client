<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

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
    public function __construct($clientId = '', $correlationId = 0)
    {
        parent::__construct(Kafka::LIST_GROUPS, $clientId, $correlationId);
    }
}
