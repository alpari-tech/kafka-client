<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The replica is not available for the requested topic-partition
 */
class ReplicaNotAvailable extends KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::REPLICA_NOT_AVAILABLE, $previous);
    }
}
