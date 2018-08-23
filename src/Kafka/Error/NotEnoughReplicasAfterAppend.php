<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Messages are written to the log, but to fewer in-sync replicas than required.
 */
class NotEnoughReplicasAfterAppend extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NOT_ENOUGH_REPLICAS_AFTER_APPEND, $previous);
    }
}
