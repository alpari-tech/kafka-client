<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

/**
 * This request queries the broker about supported API versions for each command
 */
class ApiVersionsRequest extends AbstractRequest
{

    public function __construct($clientId = '', $correlationId = 0)
    {
        parent::__construct(Kafka::API_VERSIONS, $clientId, $correlationId);
    }
}
