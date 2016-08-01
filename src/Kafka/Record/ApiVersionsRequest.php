<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * This request queries the broker about supported API versions for each command
 */
class ApiVersionsRequest extends AbstractRequest
{

    public function __construct($clientId = '', $correlationId = 0)
    {
        parent::__construct(Kafka::API_VERSIONS, $clientId, $correlationId, Kafka::VERSION_0);
    }
}
