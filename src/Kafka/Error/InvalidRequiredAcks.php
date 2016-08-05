<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Produce request specified an invalid value for required acks.
 */
class InvalidRequiredAcks extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_REQUIRED_ACKS, $previous);
    }
}
