<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The coordinator is not aware of this member.
 */
class UnknownMemberId extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_GROUP_ID, $previous);
    }
}
