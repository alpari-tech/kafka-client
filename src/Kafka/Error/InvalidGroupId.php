<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The configured groupId is invalid
 */
class InvalidGroupId extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_GROUP_ID, $previous);
    }
}
