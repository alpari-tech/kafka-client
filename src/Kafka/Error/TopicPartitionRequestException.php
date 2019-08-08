<?php
/*
 * This file is part of the Alpari Kafka client.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Alpari\Kafka\Error;

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
    public function getPartialResult(): array
    {
        return $this->partialResult;
    }

    /**
     * Return array of occurred exceptions
     *
     * @return Exception[][] [topic][partition] => exception
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
