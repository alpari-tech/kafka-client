<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The controller moved to another broker.
 */
class StaleControllerEpoch extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::STALE_CONTROLLER_EPOCH, $previous);
    }
}
