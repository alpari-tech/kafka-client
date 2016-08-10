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

        Config::KEY_SERIALIZER            => null,
        Config::VALUE_SERIALIZER          => null,
        Config::BUFFER_MEMORY             => 33554432,
        Config::COMPRESSION_TYPE          => 'none',
        Config::RETRIES                   => 0,
        Config::SSL_KEY_PASSWORD          => null,
        Config::SSL_KEYSTORE_LOCATION     => null,
        Config::SSL_KEYSTORE_PASSWORD     => null,
        Config::BATCH_SIZE                => 0,
        Config::CONNECTIONS_MAX_IDLE_MS   => 540000,
        Config::LINGER_MS                 => 0,
        Config::MAX_REQUEST_SIZE          => 1048576,
        Config::RECEIVE_BUFFER_BYTES      => 32768,
        Config::REQUEST_TIMEOUT_MS        => 30000,
        Config::SASL_MECHANISM            => 'GSSAPI',
        Config::SECURITY_PROTOCOL         => 'plaintext',
        Config::SEND_BUFFER_BYTES         => 131072,
        Config::METADATA_FETCH_TIMEOUT_MS => 60000,
        Config::METADATA_MAX_AGE_MS       => 300000,
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
     * @param string  $topic   Name of the topic
     * @param Message|Message[] $message Message or array of messages to send
     * @param integer|null    $concretePartition Optional partition for sending message
     *
     * @return ProduceResponse
     */
    public function send($topic, $message, $concretePartition = null)
    {
        if (isset($concretePartition)) {
            $partition = $concretePartition;
        } else {
            $partition = $this->partitioner->partition($topic, $message->key, $message->value, $this->cluster);
        }

        $topicMessages = ($message instanceof Message) ? [$message] : (array) $message;
        $response      = $this->client->produce($topic, $partition, $topicMessages);

        return $response;
    }
}
