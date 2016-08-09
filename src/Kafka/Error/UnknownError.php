<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The server experienced an unexpected error when processing the request
 */
class UnknownError extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::UNKNOWN, $previous);
    }
}
