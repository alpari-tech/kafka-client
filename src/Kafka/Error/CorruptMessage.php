<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This message has failed its CRC checksum, exceeds the valid size, or is otherwise corrupt.
 */
class CorruptMessage extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::OFFSET_OUT_OF_RANGE, $previous);
    }
}
