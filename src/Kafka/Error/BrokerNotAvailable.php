<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The broker is not available.
 */
class BrokerNotAvailable extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::BROKER_NOT_AVAILABLE, $previous);
    }
}
