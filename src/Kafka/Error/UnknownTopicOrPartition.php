<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This server does not host this topic-partition.
 */
class UnknownTopicOrPartition extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::UNKNOWN_TOPIC_OR_PARTITION, $previous);
    }
}
