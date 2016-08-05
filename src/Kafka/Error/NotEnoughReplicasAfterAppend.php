<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Messages are written to the log, but to fewer in-sync replicas than required.
 */
class NotEnoughReplicasAfterAppend extends KafkaException implements RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::NOT_ENOUGH_REPLICAS_AFTER_APPEND, $previous);
    }
}
