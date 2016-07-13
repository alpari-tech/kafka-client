<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The broker does not support the requested SASL mechanism.
 */
class UnsupportedSaslMechanism extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::UNSUPPORTED_SASL_MECHANISM, $previous);
    }
}
