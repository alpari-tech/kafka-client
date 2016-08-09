<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The requested fetch size is invalid.
 */
class InvalidFetchSize extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_FETCH_SIZE, $previous);
    }
}
