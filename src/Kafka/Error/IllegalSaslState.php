<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Request is not valid given the current SASL state.
 */
class IllegalSaslState extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::ILLEGAL_SASL_STATE, $previous);
    }
}
