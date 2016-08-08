<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka;
use Protocol\Kafka\Error\InvalidTopicException;
use Protocol\Kafka\Error\OffsetOutOfRange;
use Protocol\Kafka\Record;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Stream;

/**
 * A representation of a subset of the nodes, topics, and partitions in the Kafka cluster.
 */
final class Cluster
{
    /**
     * List of broker nodes
     *
     * @var Node[]|array
     */
    private $nodes = [];

    /**
     * Topic partitions
     *
     * @var TopicPartition[]|array
     */
    private $topicPartitions = [];

    /**
     * Creates a new cluster with the given nodes and partitions
     */
    private function __construct(array $nodes, array $topicPartitions)
    {
        $this->nodes           = $nodes;
        $this->topicPartitions = $topicPartitions;
    }

    /**
     * Gets the list of available partitions for this topic
     *
     * @param string $topic Name of the topic
     *
     * @return array|PartitionInfo[]
     */
    public function availablePartitionsForTopic($topic)
    {
        if (!isset($this->topicPartitions[$topic])) {
            throw new InvalidTopicException("Topic {$topic} was not found");
        }

        return $this->topicPartitions[$topic]->partitions;
    }

    /**
     * Creates a "bootstrap" cluster using the given list of host/ports
     *
     * @param array $brokerAddresses List of broker addresses for bootstraping
     * @param bool  $usePersistent Flag to enable the usage of persistent sockets for connections
     *
     * @return Cluster
     */
    public static function bootstrap(array $brokerAddresses, $usePersistent = true)
    {
        $streamClass = $usePersistent ? Stream\PersistentSocketStream::class : Stream\SocketStream::class;
        foreach ($brokerAddresses as $address) {
            try {
                $stream = new $streamClass($address);
                break;
            } catch (NetworkException $e) {
                // we ignore all network errors and just try the next one address
                continue;
            }
        }
        if (!isset($stream)) {
            throw new NetworkException("There are no available brokers for bootstraping");
        }
        $metadata = self::fetchMetadata($stream);
        $cluster  = new Cluster($metadata->brokers, $metadata->topics);

        return $cluster;
    }

    /**
     * Gets the current leader for the given topic-partition
     *
     * @param string  $topic     Name of the topic
     * @param integer $partition Number of the partition
     *
     * @return Node
     */
    public function leaderFor($topic, $partition)
    {
        $partitions = $this->partitionsForTopic($topic);
        if (!isset($partitions[$partition])) {
            throw new OffsetOutOfRange("Partition {$partition} is out of range for topic {$topic}");
        }

        $leaderId = $partitions[$partition]->leader;

        return $this->nodes[$leaderId];
    }

    /**
     * Gets the node by the node id (or null if no such node exists)
     *
     * @param integer $nodeId Node identifier
     *
     * @return null|Node
     */
    public function nodeById($nodeId)
    {
        if (!isset($this->nodes[$nodeId])) {
            return null;
        }

        return $this->nodes[$nodeId];
    }

    /**
     * Returns the list of nodes in the cluster
     *
     * @return Node[]
     */
    public function nodes()
    {
        return $this->nodes;
    }

    /**
     * Gets the metadata for the specified partition
     *
     * @param string  $topic     Name of the topic
     * @param integer $partition Number of the partition
     *
     * @return PartitionInfo
     */
    public function partition($topic, $partition)
    {
        $partitions = $this->partitionsForTopic($topic);
        if (!isset($partitions[$partition])) {
            throw new OffsetOutOfRange("Partition {$partition} is out of range for topic {$topic}");
        }

        return $partitions[$partition];
    }

    /**
     * Gets the list of partitions for this topic
     *
     * @param string $topic Name of the topic
     *
     * @return PartitionInfo[]
     */
    public function partitionsForTopic($topic)
    {
        if (!isset($this->topicPartitions[$topic])) {
            throw new InvalidTopicException("Topic {$topic} was not found");
        }

        return $this->topicPartitions[$topic]->partitions;
    }

    /**
     * Get all topics
     *
     * @return string[]
     */
    public function topics()
    {
        return array_keys($this->topicPartitions);
    }

    /**
     * Queries metadata from the broker
     *
     * @param Stream $stream
     *
     * @return Record\MetadataResponse
     */
    private static function fetchMetadata(Stream $stream)
    {
        $request = new Record\MetadataRequest();
        $request->writeTo($stream);

        return Record\MetadataResponse::unpack($stream);
    }
}
