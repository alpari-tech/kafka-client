<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The requested offset is not within the range of offsets maintained by the server.
 */
class OffsetOutOfRange extends \OutOfRangeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::OFFSET_OUT_OF_RANGE, $previous);
    }
}
