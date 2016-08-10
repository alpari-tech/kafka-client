<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka;
use Protocol\Kafka\Error\InvalidTopicException;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Error\UnknownTopicOrPartition;
use Protocol\Kafka\Record;
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
            throw new InvalidTopicException(compact('topic'));
        }

        return $this->topicPartitions[$topic]->partitions;
    }

    /**
     * Creates a "bootstrap" cluster using the given list of host/ports
     *
     * @param array $configuration Broker client configuration
     *
     * @return Cluster
     */
    public static function bootstrap(array $configuration)
    {
        $brokerAddresses = [];
        if (isset($configuration[Config::BOOTSTRAP_SERVERS])) {
            $brokerAddresses = $configuration[Config::BOOTSTRAP_SERVERS];
        };

        foreach ($brokerAddresses as $address) {
            try {
                $metadata = self::loadOrFetchMetadata($address, $configuration);
                $cluster  = new Cluster($metadata->brokers, $metadata->topics);

                return $cluster;
            } catch (NetworkException $e) {
                // we ignore all network errors and just try the next one address
                continue;
            }
        }
        // If we here, we can't access any broker node, terminate
        throw new NetworkException(compact('brokerAddresses'));
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
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
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
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
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
            throw new InvalidTopicException(compact('topic'));
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
     * Queries metadata from the broker or loads this information from the cache file
     *
     * @param string $address Concrete broker address
     * @param array $configuration
     *
     * @return Record\MetadataResponse
     */
    private static function loadOrFetchMetadata($address, array $configuration)
    {
        $milliSeconds   = (int)(microtime(true) * 1e3);
        $isCacheEnabled = !empty($configuration[Config::METADATA_CACHE_FILE]);
        $metadata       = null;

        if ($isCacheEnabled) {
            $cacheFile = $configuration[Config::METADATA_CACHE_FILE];
            if (is_readable($cacheFile)) {
                list($cachePutTimeMs, $metadata) = include $cacheFile;
            } else {
                $cachePutTimeMs = $milliSeconds;
            }
            if (($milliSeconds - $cachePutTimeMs) > $configuration[Config::METADATA_MAX_AGE_MS]) {
                $metadata = null;
            }
        }

        if (empty($metadata)) {
            $stream  = new Stream\SocketStream($address, $configuration);
            $request = new Record\MetadataRequest();
            $request->writeTo($stream);

            $metadata = Record\MetadataResponse::unpack($stream);
        }

        if ($isCacheEnabled && isset($stream)) {
            $content   = '<?php return ' . var_export([$milliSeconds, $metadata], true) . ';';
            $cacheFile = $configuration[Config::METADATA_CACHE_FILE];
            file_put_contents($cacheFile, $content);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($cacheFile, true);
            }
        }

        return $metadata;
    }
}
