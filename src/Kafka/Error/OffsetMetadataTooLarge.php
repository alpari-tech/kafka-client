<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The metadata field of the offset request was too large.
 */
class OffsetMetadataTooLarge extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::OFFSET_METADATA_TOO_LARGE, $previous);
    }
}
