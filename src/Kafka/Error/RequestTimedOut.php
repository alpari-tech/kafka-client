<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request timed out.
 */
class RequestTimedOut extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::REQUEST_TIMED_OUT, $previous);
    }
}
