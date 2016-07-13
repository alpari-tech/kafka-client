<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The session timeout is not within the range allowed by the broker
 *
 * as configured by group.min.session.timeout.ms and group.max.session.timeout.ms
 */
class InvalidSessionTimeout extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_SESSION_TIMEOUT, $previous);
    }
}
