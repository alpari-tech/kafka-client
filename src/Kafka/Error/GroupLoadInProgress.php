<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The coordinator is loading and hence can't process requests for this group.
 */
class GroupLoadInProgress extends KafkaException implements RetriableException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::GROUP_LOAD_IN_PROGRESS, $previous);
    }
}
