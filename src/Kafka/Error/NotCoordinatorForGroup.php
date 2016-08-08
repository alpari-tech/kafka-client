<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This is not the correct coordinator for this group.
 */
class NotCoordinatorForGroup extends KafkaException implements RetriableException
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NOT_COORDINATOR_FOR_GROUP, $previous);
    }
}
