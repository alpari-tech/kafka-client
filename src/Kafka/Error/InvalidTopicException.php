<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request attempted to perform an operation on an invalid topic.
 */
class InvalidTopicException extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_TOPIC_EXCEPTION, $previous);
    }
}
