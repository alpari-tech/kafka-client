<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The controller moved to another broker.
 */
class StaleControllerEpoch extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::STALE_CONTROLLER_EPOCH, $previous);
    }
}
