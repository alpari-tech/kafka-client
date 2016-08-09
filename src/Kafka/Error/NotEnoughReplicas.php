<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Messages are rejected since there are fewer in-sync replicas than required.
 */
class NotEnoughReplicas extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NOT_ENOUGH_REPLICAS, $previous);
    }
}
