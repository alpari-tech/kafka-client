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
