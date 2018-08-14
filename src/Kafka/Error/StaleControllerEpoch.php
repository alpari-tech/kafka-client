<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The controller moved to another broker.
 */
class StaleControllerEpoch extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::STALE_CONTROLLER_EPOCH, $previous);
    }
}
