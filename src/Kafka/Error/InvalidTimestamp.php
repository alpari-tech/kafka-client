<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The timestamp of the message is out of acceptable range.
 */
class InvalidTimestamp extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_TIMESTAMP, $previous);
    }
}
