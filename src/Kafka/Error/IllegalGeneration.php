<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Specified group generation id is not valid.
 */
class IllegalGeneration extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::ILLEGAL_GENERATION, $previous);
    }
}
