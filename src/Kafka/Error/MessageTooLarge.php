<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request included a message larger than the max message size the server will accept.
 */
class MessageTooLarge extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::MESSAGE_TOO_LARGE, $previous);
    }
}
