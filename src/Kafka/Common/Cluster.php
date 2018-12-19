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

namespace Alpari\Kafka\Common;

use Alpari\Kafka\Error\InvalidTopicException;
use Alpari\Kafka\Error\KafkaException;
use Alpari\Kafka\Error\NetworkException;
use Alpari\Kafka\Error\UnknownError;
use Alpari\Kafka\Error\UnknownTopicOrPartition;
use Alpari\Kafka\Record\MetadataRequest;
use Alpari\Kafka\Record\MetadataResponse;
use Alpari\Kafka\Stream;

/**
 * A representation of a subset of the nodes, topics, and partitions in the Kafka cluster.
 *
 * @TODO loadFromCache() method contains unsfafe file inclusion, possible vector for attack
 */
final class Cluster
{
    /**
     * List of broker nodes
     *
     * @var Node[]
     */
    private $nodes = [];

    /**
     * Topic partitions
     *
     * @var TopicMetadata[]
     */
    private $topicPartitions = [];

    /**
     * Client configuration
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
    public static function bootstrap(array $configuration): Cluster
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
     * @throws UnknownTopicOrPartition If topic-partition was not found
     */
    public function leaderFor(string $topic, int $partition): Node
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
     */
    public function nodeById(int $nodeId): ?Node
    {
        if (!isset($this->nodes[$nodeId])) {
            // Try to reload cluster in the case if configuration was changed
            $this->reload();
        }

        // Either we have a node (maybe after reload) or just return null
        return $this->nodes[$nodeId] ?? null;
    }

    /**
     * Returns the list of nodes in the cluster
     *
     * @return Node[]
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * Gets the metadata for the specified partition
     */
    public function partition(string $topic, int $partition): PartitionMetadata
    {
        $partitions = $this->partitionsForTopic($topic);
        if (!isset($partitions[$partition])) {
            throw new UnknownTopicOrPartition(compact('topic', 'partition'));
        }

        return $partitions[$partition];
    }

    /**
     * Gets the list of partitions for specified topic
     *
     * @return PartitionMetadata[]
     */
    public function partitionsForTopic(string $topic): array
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
    public function reload(): void
    {
        $brokerAddresses = $this->configuration[Config::BOOTSTRAP_SERVERS] ?? [];

        $cause = [];
        foreach ($brokerAddresses as $address) {
            try {
                $stream  = new Stream\SocketStream($address, $this->configuration);
                $request = new MetadataRequest();
                $request->writeTo($stream);

                $metadata = MetadataResponse::unpack($stream);
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
    public function topics(): array
    {
        return array_keys($this->topicPartitions);
    }

    /**
     * Loads cluster configuration from the cache
     *
     * @return boolean True if metadata was successfully loaded from the cache
     */
    private function loadFromCache(): bool
    {
        $milliSeconds = (int)(microtime(true) * 1e3);
        $cacheFile    = $this->configuration[Config::METADATA_CACHE_FILE];
        if (is_readable($cacheFile)) {
            /** @var MetadataResponse $metadata */
            [$cachePutTimeMs, $metadata] = include $cacheFile;
            if (($milliSeconds - $cachePutTimeMs) < $this->configuration[Config::METADATA_MAX_AGE_MS]) {
                $this->nodes           = $metadata->brokers;
                $this->topicPartitions = $metadata->topics;

                return true;
            }
        }

        return false;
    }
}
