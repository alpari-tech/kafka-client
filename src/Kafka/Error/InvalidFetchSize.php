<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The requested fetch size is invalid.
 */
class InvalidFetchSize extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_FETCH_SIZE, $previous);
    }
}
