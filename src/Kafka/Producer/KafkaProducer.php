<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Producer;

use Protocol\Kafka\Client;
use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\PartitionMetadata;
use Protocol\Kafka\DTO\Record;
use Protocol\Kafka\Error\NotLeaderForPartition;
use Protocol\Kafka\Error\RetriableException;

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
    private $partitioner = null;

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
     * @var array
     */
    private $topicPartitionMessages = [];

    public function __construct(array $configuration = [])
    {
        $this->configuration = ($configuration + Config::getDefaultConfiguration());
        $partitioner         = $this->configuration[Config::PARTITIONER_CLASS];

        if (!is_subclass_of($partitioner, PartitionerInterface::class)) {
            throw new \InvalidArgumentException("Partitioner class should implement PartitionInterface");
        }
        $this->partitioner = new $partitioner;
    }

    /**
     * Invoking this method makes all buffered records immediately available to send and blocks on the completion of
     * the requests associated with these records.
     */
    public function flush()
    {
        $result           = null;
        $this->currentTry = 0;

        $exceptions = [];
        while ($this->currentTry <= $this->configuration[Config::RETRIES]) {
            try {
                $result = $this->getClient()->produce($this->topicPartitionMessages);
                // TODO: resolve futures or store result for analysis
                $this->batchSize = 0;

                $this->topicPartitionMessages = [];
                break;
            } catch (NotLeaderForPartition $exception) {
                // We just need to reconfigure the cluster, possible current leader is changed
                $this->getCluster()->reload();
            } catch (RetriableException $exception) {
                $this->getCluster()->reload();
                $this->currentTry++;
                $message              = $exception->getMessage();
                $exceptions[$message] = isset($exceptions[$message]) ? $exceptions[$message] + 1 : 1;
            }
        }

        if ($this->currentTry > $this->configuration[Config::RETRIES]) {
            $message         = '';
            $totalExceptions = array_sum($exceptions);
            $index           = 1;
            foreach ($exceptions as $msg => $count) {
                $message .= "$index. $msg ($count / $totalExceptions)\n";
                $index   += 1;
            }
            throw new \RuntimeException("Can not deliver messages to the broker:\n$message");
        }

        return $result;
    }

    /**
     * Gets the partition metadata for the given topic.
     *
     * @param string $topic
     *
     * @return PartitionMetadata[]
     */
    public function partitionsFor($topic)
    {
        return $this->getCluster()->partitionsForTopic($topic);
    }

    /**
     * Sends a message to the topic
     *
     * @todo Use futures instead of void result
     *
     * @param string       $topic             Name of the topic
     * @param Record       $message           Message to send
     * @param integer|null $concretePartition Optional partition for sending message
     *
     * @return array
     */
    public function send($topic, Record $message, $concretePartition = null)
    {
        if (isset($concretePartition)) {
            $partition = $concretePartition;
        } else {
            $partition = $this->partitioner->partition($topic, $message->key, $message->value, $this->getCluster());
        }

        $this->topicPartitionMessages[$topic][$partition][] = $message;
        $this->batchSize++;

        if ($this->batchSize < $this->configuration[Config::BATCH_SIZE]) {
            // Return nothing, however it would be nice to return a Promise
            return [];
        }

        return $this->flush();
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
     *
     * @return Cluster
     */
    private function getCluster()
    {
        if (!$this->cluster) {
            $this->cluster = Cluster::bootstrap($this->configuration);
        }

        return $this->cluster;
    }

    /**
     * Lazy-loading for kafka client
     *
     * @return Client
     */
    private function getClient()
    {
        if (!$this->client) {
            $this->client = new Client($this->getCluster(), $this->configuration);
        }

        return $this->client;
    }
}
