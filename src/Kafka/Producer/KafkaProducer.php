<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Producer;

use Protocol\Kafka\Client;
use Protocol\Kafka\Common\Cluster;
use Protocol\Kafka\Common\PartitionInfo;
use Protocol\Kafka\DTO\Message;
use Protocol\Kafka\DTO\ProduceResponsePartition;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Error\NotLeaderForPartition;
use Protocol\Kafka\Error\RetriableException;
use Protocol\Kafka\Error\UnknownTopicOrPartition;
use Protocol\Kafka\Record\ProduceResponse;

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

    /**
     * Default configuration for producer
     *
     * @var array
     */
    private static $defaultConfiguration = [
        /* Used configs */
        Config::BOOTSTRAP_SERVERS            => [],
        Config::PARTITIONER_CLASS            => DefaultPartitioner::class,
        Config::ACKS                         => 1,
        Config::TIMEOUT_MS                   => 2000,
        Config::CLIENT_ID                    => 'PHP/Kafka',
        Config::STREAM_PERSISTENT_CONNECTION => false,
        Config::STREAM_ASYNC_CONNECT         => false,
        Config::METADATA_MAX_AGE_MS          => 300000,
        Config::RECEIVE_BUFFER_BYTES         => 32768,
        Config::SEND_BUFFER_BYTES            => 131072,
        Config::RETRIES                      => 0,
        Config::BATCH_SIZE                   => 0,

        Config::COMPRESSION_TYPE          => 'none',
        Config::SSL_KEY_PASSWORD          => null,
        Config::SSL_KEYSTORE_LOCATION     => null,
        Config::SSL_KEYSTORE_PASSWORD     => null,
        Config::CONNECTIONS_MAX_IDLE_MS   => 540000,
        Config::LINGER_MS                 => 0,
        Config::MAX_REQUEST_SIZE          => 1048576,
        Config::REQUEST_TIMEOUT_MS        => 30000,
        Config::SASL_MECHANISM            => 'GSSAPI',
        Config::SECURITY_PROTOCOL         => 'plaintext',
        Config::METADATA_FETCH_TIMEOUT_MS => 60000,
        Config::RECONNECT_BACKOFF_MS      => 50,
        Config::RETRY_BACKOFF_MS          => 100,
    ];

    public function __construct(array $configuration = [])
    {
        $this->configuration = ($configuration + self::$defaultConfiguration);
        $this->cluster       = Cluster::bootstrap($this->configuration);
        $partitioner         = $this->configuration[Config::PARTITIONER_CLASS];

        if (!is_subclass_of($partitioner, PartitionerInterface::class)) {
            throw new \InvalidArgumentException("Partitioner class should implement PartitionInterface");
        }
        $this->partitioner = new $partitioner;
        $this->client      = new Client($this->cluster, $this->configuration);
    }

    /**
     * Gets the partition metadata for the given topic.
     *
     * @param string $topic
     *
     * @return PartitionInfo[]
     */
    public function partitionsFor($topic)
    {
        return $this->cluster->partitionsForTopic($topic);
    }

    /**
     * Sends a message to the topic
     *
     * @todo Use futures instead of void result
     *
     * @param string  $topic   Name of the topic
     * @param Message $message Message to send
     * @param integer|null    $concretePartition Optional partition for sending message
     *
     * @return void
     */
    public function send($topic, Message $message, $concretePartition = null)
    {
        $this->currentTry = 0;
        if (isset($concretePartition)) {
            $partition = $concretePartition;
        } else {
            $partition = $this->partitioner->partition($topic, $message->key, $message->value, $this->cluster);
        }

        $this->topicPartitionMessages[$topic][$partition][] = $message;
        $this->batchSize++;

        if ($this->batchSize < $this->configuration[Config::BATCH_SIZE]) {
            return;
        }

        while ($this->currentTry <= $this->configuration[Config::RETRIES]) {
            try {
                $this->client->produce($this->topicPartitionMessages);
                // TODO: resolve futures or store result for analysis
                $this->batchSize = 0;

                $this->topicPartitionMessages = [];
                break;
            } catch (NotLeaderForPartition $exception) {
                // We just need to reconfigure the cluster, possible current leader is changed
                $this->cluster->reload();
            } catch (RetriableException $exception) {
                $this->cluster->reload();
                $this->currentTry++;
            }
        }

        if ($this->currentTry > $this->configuration[Config::RETRIES]) {
            throw new \RuntimeException("Can not deliver messages to the broker");
        }
    }
}
