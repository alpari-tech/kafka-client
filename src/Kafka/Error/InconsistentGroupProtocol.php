<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The group member's supported protocols are incompatible with those of existing members.
 */
class InconsistentGroupProtocol extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INCONSISTENT_GROUP_PROTOCOL, $previous);
    }
}
