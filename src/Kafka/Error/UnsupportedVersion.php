<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The version of API is not supported.
 */
class UnsupportedVersion extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::UNSUPPORTED_VERSION, $previous);
    }
}
