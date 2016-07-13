<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Not authorized to access topics: [Topic authorization failed.]
 */
class TopicAuthorizationFailed extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::TOPIC_AUTHORIZATION_FAILED, $previous);
    }
}
