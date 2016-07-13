<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Messages are rejected since there are fewer in-sync replicas than required.
 */
class NotEnoughReplicas extends \RuntimeException implements KafkaException, RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::NOT_ENOUGH_REPLICAS, $previous);
    }
}
