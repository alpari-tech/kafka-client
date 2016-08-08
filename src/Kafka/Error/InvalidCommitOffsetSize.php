<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The committing offset data size is not valid
 */
class InvalidCommitOffsetSize extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_COMMIT_OFFSET_SIZE, $previous);
    }
}
