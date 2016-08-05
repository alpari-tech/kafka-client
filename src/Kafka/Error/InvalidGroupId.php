<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The configured groupId is invalid
 */
class InvalidGroupId extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_GROUP_ID, $previous);
    }
}
