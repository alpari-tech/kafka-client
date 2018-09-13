<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Class TopicPartitionRequestException
 */
class TopicPartitionRequestException extends \RuntimeException implements ServerExceptionInterface
{
    /**
     * Partial result ready to use [topic][partition] => partition result
     *
     * @var array
     */
    private $partialResult;

    /**
     * Exceptions during this query [topic][partition] => exception
     *
     * @var Exception[][]
     */
    protected $exceptions;

    /**
     * TopicPartitionRequestException constructor.
     *
     * @param array         $partialResult Partial result received from broker
     * @param Exception[][] $exceptions    List of nested exceptions
     */
    public function __construct(array $partialResult, array $exceptions)
    {
        $message = '';
        foreach ($exceptions as $topic => $partitions) {
            foreach ($partitions as $partitionId => $exception) {
                $message .= sprintf("%s:%s %s\n", $topic, $partitionId, $exception->getMessage());
            }
        }
        parent::__construct('Request completed with errors: ' . $message);
        $this->partialResult = $partialResult;
        $this->exceptions    = $exceptions;
    }

    /**
     * Return partial result from request
     *
     * @return array [topic][partition] => partition result
     */
    public function getPartialResult()
    {
        return $this->partialResult;
    }

    /**
     * Return array of occurred exceptions
     *
     * @return Exception[][] [topic][partition] => exception
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
}
