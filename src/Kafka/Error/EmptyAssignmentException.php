<?php

namespace Protocol\Kafka\Error;

use Exception;
use RuntimeException;

/**
 * Consumer did not receive any subscription from the leader
 */
class EmptyAssignmentException extends RuntimeException implements ClientExceptionInterface
{
    /**
     * List of requested topic subscription
     *
     * @var string[]
     */
    private $requestedTopics;

    /**
     * EmptyAssignmentException constructor.
     *
     * @param string[]       $requestedTopics List of requested topic subscription
     * @param Exception|null $previous        Previous exception if there was one
     */
    public function __construct(array $requestedTopics, Exception $previous = null)
    {
        parent::__construct('Consumer did not receive any subscription from the leader.', 0, $previous);
        $this->requestedTopics = $requestedTopics;
    }

    /**
     * Return list of requested topic subscription
     *
     * @return string[]
     */
    public function getRequestedTopics()
    {
        return $this->requestedTopics;
    }
}
