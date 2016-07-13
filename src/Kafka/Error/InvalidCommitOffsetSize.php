<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The committing offset data size is not valid
 */
class InvalidCommitOffsetSize extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::INVALID_COMMIT_OFFSET_SIZE, $previous);
    }
}
