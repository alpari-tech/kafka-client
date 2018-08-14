<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The timestamp of the message is out of acceptable range.
 */
class InvalidTimestamp extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_TIMESTAMP, $previous);
    }
}
