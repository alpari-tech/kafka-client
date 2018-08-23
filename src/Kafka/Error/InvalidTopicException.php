<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request attempted to perform an operation on an invalid topic.
 */
class InvalidTopicException extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_TOPIC_EXCEPTION, $previous);
    }
}
