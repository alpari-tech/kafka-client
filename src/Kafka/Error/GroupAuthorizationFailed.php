<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Not authorized to access group: Group authorization failed.
 */
class GroupAuthorizationFailed extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::GROUP_AUTHORIZATION_FAILED, $previous);
    }
}
