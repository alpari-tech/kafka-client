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


namespace Alpari\Kafka\Producer;

use Alpari\Kafka\Client;
use Alpari\Kafka\Common\Cluster;
use Alpari\Kafka\Common\PartitionMetadata;
use Alpari\Kafka\DTO\ProduceRequestTopic;
use Alpari\Kafka\DTO\Record;
use Alpari\Kafka\Error\NotLeaderForPartition;
use Alpari\Kafka\Error\RetriableException;
use Alpari\Kafka\Error\TopicPartitionRequestException;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * A Kafka client that publishes records to the Kafka cluster.
 */
class KafkaProducer
{
    /**
     * The producer configs
     *
     * @var array
     */
    private $configuration;

    /**
     * Kafka cluster configuration
     *
     * @var Cluster
     */
    private $cluster;

    /**
     * Low-level kafka client
     *
     * @var Client
     */
    private $client;

    /**
     * Instance of partitioner
     *
     * @var PartitionerInterface
     */
    private $partitioner;

    /**
     * Current iteration of sending data
     *
     * @var int
     */
    private $currentTry = 0;

    /**
     * Size of the batch
     *
     * @var int
     */
    private $batchSize = 0;

    /**
     * Buffer for storing topic-partition-messages
     *
     * @var ProduceRequestTopic[]
     */
    private $topicPartitionMessages = [];

    /**
     * Deferred task to send messages for each topic-partition
     *
     * We don't need this per each record because entire record batch is commited to the partition
     *
     * @var Deferred[][]
     */
    private $deferredTopicPartitionSend = [];

    public function __construct(array $configuration = [])
    {
        $this->configuration = ($configuration + Config::getDefaultConfiguration());
        $partitioner         = $this->configuration[Config::PARTITIONER_CLASS];

        if (!is_subclass_of($partitioner, PartitionerInterface::class)) {
            throw new \InvalidArgumentException('Partitioner class should implement PartitionInterface');
        }
        $this->partitioner = new $partitioner;
    }

    /**
     * Invoking this method makes all buffered records immediately available to send and blocks on the completion of
     * the requests associated with these records.
     */
    public function flush(): void
    {
        $exceptions = [];
        $this->currentTry  = 0;

        while ($this->currentTry <= $this->configuration[Config::RETRIES]) {
            $produceResult     = [];
            $produceExceptions = [];
            try {
                $produceResult = $this->getClient()->produce($this->topicPartitionMessages);
                break;
            } catch (TopicPartitionRequestException $exception) {
                //TODO: For transaction mode we should just retry the transaction, no partial results

                // We have partial result on one part of topic-partition(s) and error(s) on another
                $produceResult     = $exception->getPartialResult();
                $produceExceptions = $exception->getExceptions();
            } finally {
                // If we have any result, we can process it
                foreach ($produceResult as $topic => $partitions) {
                    foreach ($partitions as $partitionId => $partitionResult) {
                        // Batch size should be partially decremented
                        $this->batchSize -= count($this->topicPartitionMessages[$topic][$partitionId]);

                        // Exclude completed partitions from subsequent retries. Unsafe for partial result!
                        unset(
                            $this->topicPartitionMessages[$topic][$partitionId],
                            $exceptions[$topic][$partitionId] // Also clear previous errors if succeeded
                        );

                        // Resolve deferred promise with partition result
                        $this->deferredTopicPartitionSend[$topic][$partitionId]->resolve($partitionResult);
                    }
                }
                // If we have any exceptions, then process them
                foreach ($produceExceptions as $topic => $partitionExceptions) {
                    $exceptions[$topic] = ($exceptions[$topic] ?? []) + $partitionExceptions;
                }
                //TODO: Check retriable exceptions
                if (!empty($produceExceptions)) {
                    usleep(1000 * $this->configuration[Config::RETRY_BACKOFF_MS]);
                    $this->currentTry++;
                    $this->getCluster()->reload();
                }
            }
        }

        // If we have exceptions after retries then fail promises
        foreach ($exceptions as $topic => $partitions) {
            foreach ($partitions as $partitionId => $partitionException) {
                // Reject deferred promise with partition exception
                $this->deferredTopicPartitionSend[$topic][$partitionId]->reject($partitionException);
            }
        }
    }

    /**
     * Gets the partition metadata for the given topic.
     *
     * @return PartitionMetadata[]
     */
    public function partitionsFor(string $topic): array
    {
        return $this->getCluster()->partitionsForTopic($topic);
    }

    /**
     * Sends a message to the topic
     *
     * @param string       $topic             Name of the topic
     * @param Record       $message           Message to send
     * @param integer|null $concretePartition Optional partition for sending message
     *
     * @return Promise
     */
    public function send(string $topic, Record $message, ?int $concretePartition = null): Promise
    {
        if (isset($concretePartition)) {
            $partition = $concretePartition;
        } else {
            $partition = $this->partitioner->partition($topic, $message->key, $message->value, $this->getCluster());
        }

        $this->topicPartitionMessages[$topic][$partition][] = $message;
        $this->batchSize++;

        if (!isset($this->deferredTopicPartitionSend[$topic][$partition])) {
            $this->deferredTopicPartitionSend[$topic][$partition] = new Deferred();
        }

        $promise = $this->deferredTopicPartitionSend[$topic][$partition]->promise();

        if ($this->batchSize >= $this->configuration[Config::BATCH_SIZE]) {
            $this->flush();
        }

        return $promise;
    }

    /**
     * Automatic flushing of all waiting messages, to use async flush, just call fastcgi_finish_request() before
     */
    public function __destruct()
    {
        if (!empty($this->topicPartitionMessages)) {
            $this->flush();
        }
    }

    /**
     * Cluster lazy-loading
     */
    private function getCluster(): Cluster
    {
        if (!$this->cluster) {
            $this->cluster = Cluster::bootstrap($this->configuration);
        }

        return $this->cluster;
    }

    /**
     * Lazy-loading for kafka client
     */
    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client($this->getCluster(), $this->configuration);
        }

        return $this->client;
    }
}
