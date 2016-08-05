<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The request included message batch larger than the configured segment size on the server.
 */
class RecordListTooLarge extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::RECORD_LIST_TOO_LARGE, $previous);
    }
}
