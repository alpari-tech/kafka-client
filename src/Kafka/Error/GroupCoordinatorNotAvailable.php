<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The group coordinator is not available.
 */
class GroupCoordinatorNotAvailable extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::GROUP_LOAD_IN_PROGRESS, $previous);
    }
}
