<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Produce request specified an invalid value for required acks.
 */
class InvalidRequiredAcks extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_REQUIRED_ACKS, $previous);
    }
}
