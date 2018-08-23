<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The metadata field of the offset request was too large.
 */
class OffsetMetadataTooLarge extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::OFFSET_METADATA_TOO_LARGE, $previous);
    }
}
