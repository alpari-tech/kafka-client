<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This message has failed its CRC checksum, exceeds the valid size, or is otherwise corrupt.
 */
class CorruptMessage extends \RuntimeException implements KafkaException, RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::OFFSET_OUT_OF_RANGE, $previous);
    }
}
