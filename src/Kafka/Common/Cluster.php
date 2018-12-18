<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka\Error\InvalidTopicException;
use Protocol\Kafka\Error\KafkaException;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Error\UnknownError;
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
     * @var TopicMetadata[]|array
     */
    private $topicPartitions = [];

    /**
     * Client configuration
     *
     * @var array
     */
    private $configuration;

    /**
     * Creates a new cluster with the given nodes and partitions
     *
     * @param array $configuration Client configuration
     */
    private function __construct(array $configuration)
    {
        $this->configuration = $configuration;
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
        $cluster        = new Cluster($configuration);
        $isCacheEnabled = !empty($configuration[Config::METADATA_CACHE_FILE]);
        $isLoaded       = false;

        if ($isCacheEnabled) {
            $isLoaded = $cluster->loadFromCache();
        }
        if (!$isLoaded) {
            $cluster->reload();
        }

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
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        $meta = $partitions[$partition];
        if ($meta->partitionErrorCode !== KafkaException::NO_ERROR) {
            throw KafkaException::fromCode($meta->partitionErrorCode, compact('topic', 'partition'));
        }

        $leaderId = $meta->leader;
        if (!isset($this->nodes[$leaderId])) {
            throw new UnknownError(
                [
                    'message'         => 'Can not find node for leader',
                    'topic'           => $topic,
                    'partition'       => $partition,
                    'leader'          => $leaderId,
                    'topicPartitions' => $this->topicPartitions,
                    'nodes'           => $this->nodes,
                ]
            );
        }

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
            $this->reload();
            if (!isset($this->nodes[$nodeId])) {
                throw new UnknownError(compact('nodeId') + ['error' => 'Node was not found']);
            }
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
     * @return PartitionMetadata
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
     * @return PartitionMetadata[]
     */
    public function partitionsForTopic($topic)
    {
        if (!isset($this->topicPartitions[$topic])) {
            $this->reload();
            if (!isset($this->topicPartitions[$topic])) {
                throw new InvalidTopicException(compact('topic'));
            }
        }

        $meta = $this->topicPartitions[$topic];
        if ($meta->topicErrorCode !== KafkaException::NO_ERROR) {
            throw KafkaException::fromCode($meta->topicErrorCode, compact('topic'));
        }

        return $meta->partitions;
    }

    /**
     * Reloads the metadata from the broker and optionally save it in the cache
     *
     * @throws UnknownError If information can not be reloaded
     */
    public function reload()
    {
        $brokerAddresses = $this->configuration[Config::BOOTSTRAP_SERVERS] ?? [];

        $cause = [];
        foreach ($brokerAddresses as $address) {
            try {
                $stream  = new Stream\SocketStream($address, $this->configuration);
                $request = new Record\MetadataRequest();
                $request->writeTo($stream);

                $metadata = Record\MetadataResponse::unpack($stream);
                break;
            }  catch (NetworkException $e) {
                // we ignore all network errors and just try the next one address
                $cause[$address] = $e->getMessage();
                continue;
            }
        }
        if (empty($metadata)) {
            throw new UnknownError(
                [
                    'error' => 'Can not fetch information about cluster metadata',
                    'cause' => $cause
                ]
            );
        }

        $isCacheEnabled = !empty($this->configuration[Config::METADATA_CACHE_FILE]);
        if ($isCacheEnabled) {
            $milliSeconds = (int)(microtime(true) * 1e3);
            $content      = '<?php return ' . var_export([$milliSeconds, $metadata], true) . ';';
            $cacheFile    = $this->configuration[Config::METADATA_CACHE_FILE];
            file_put_contents($cacheFile, $content);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($cacheFile, true);
            }
        }

        $this->nodes           = $metadata->brokers;
        $this->topicPartitions = $metadata->topics;
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
     * Loads cluster configuration from the cache
     *
     * @return boolean True if metadata was successfully loaded from the cache
     */
    private function loadFromCache()
    {
        $milliSeconds = (int)(microtime(true) * 1e3);
        $cacheFile    = $this->configuration[Config::METADATA_CACHE_FILE];
        if (is_readable($cacheFile)) {
            /** @var Record\MetadataResponse $metadata */
            list($cachePutTimeMs, $metadata) = include $cacheFile;
            if (($milliSeconds - $cachePutTimeMs) < $this->configuration[Config::METADATA_MAX_AGE_MS]) {
                $this->nodes           = $metadata->brokers;
                $this->topicPartitions = $metadata->topics;

                return true;
            }
        }

        return false;
    }
}
