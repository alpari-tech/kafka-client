<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The requested offset is not within the range of offsets maintained by the server.
 */
class OffsetOutOfRange extends KafkaException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::OFFSET_OUT_OF_RANGE, $previous);
    }
}
