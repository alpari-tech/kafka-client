<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * There is no leader for this topic-partition as we are in the middle of a leadership election.
 */
class LeaderNotAvailable extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::LEADER_NOT_AVAILABLE, $previous);
    }
}
