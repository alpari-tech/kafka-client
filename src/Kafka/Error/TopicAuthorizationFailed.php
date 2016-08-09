<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Not authorized to access topics
 */
class TopicAuthorizationFailed extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::TOPIC_AUTHORIZATION_FAILED, $previous);
    }
}
