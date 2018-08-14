<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request included message batch larger than the configured segment size on the server.
 */
class RecordListTooLarge extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::RECORD_LIST_TOO_LARGE, $previous);
    }
}
