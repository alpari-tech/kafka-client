<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The broker is not available.
 */
class BrokerNotAvailable extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::BROKER_NOT_AVAILABLE, $previous);
    }
}
