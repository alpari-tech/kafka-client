<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The server disconnected before a response was received.
 */
class NetworkException extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NETWORK_EXCEPTION, $previous);
    }
}
