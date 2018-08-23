<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The replica is not available for the requested topic-partition
 */
class ReplicaNotAvailable extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::REPLICA_NOT_AVAILABLE, $previous);
    }
}
