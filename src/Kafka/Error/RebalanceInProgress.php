<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The group is rebalancing, so a rejoin is needed.
 */
class RebalanceInProgress extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::REBALANCE_IN_PROGRESS, $previous);
    }
}
