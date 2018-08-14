<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The group is rebalancing, so a rejoin is needed.
 */
class RebalanceInProgress extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::REBALANCE_IN_PROGRESS, $previous);
    }
}
