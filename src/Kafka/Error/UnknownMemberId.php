<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The coordinator is not aware of this member.
 */
class UnknownMemberId extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_GROUP_ID, $previous);
    }
}
