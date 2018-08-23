<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The broker does not support the requested SASL mechanism.
 */
class UnsupportedSaslMechanism extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::UNSUPPORTED_SASL_MECHANISM, $previous);
    }
}
