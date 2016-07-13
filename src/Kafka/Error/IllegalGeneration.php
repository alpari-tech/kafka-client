<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Specified group generation id is not valid.
 */
class IllegalGeneration extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::ILLEGAL_GENERATION, $previous);
    }
}
