<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request timed out.
 */
class RequestTimedOut extends \RuntimeException implements KafkaException, RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::REQUEST_TIMED_OUT, $previous);
    }
}
