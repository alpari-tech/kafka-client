<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Not authorized to access group.
 */
class GroupAuthorizationFailed extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::GROUP_AUTHORIZATION_FAILED, $previous);
    }
}
