<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Request is not valid given the current SASL state.
 */
class IllegalSaslState extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::ILLEGAL_SASL_STATE, $previous);
    }
}
