<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * This is not the correct coordinator for this group.
 */
class NotCoordinatorForGroup extends \RuntimeException implements KafkaException, RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::NOT_COORDINATOR_FOR_GROUP, $previous);
    }
}
