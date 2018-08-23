<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This server is not the leader for that topic-partition.
 */
class NotLeaderForPartition extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NOT_LEADER_FOR_PARTITION, $previous);
    }
}
