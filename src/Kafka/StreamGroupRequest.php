<?php
/**
 * @author Alexander.Lisachenko
 * @date   17.08.2016
 */

namespace Protocol\Kafka;

use Protocol\Kafka\Common\Cluster;

class StreamGroupRequest
{
    /**
     * List of streams for the group
     *
     * @var Stream[]
     */
    private $streams;

    /**
     * Storage of resources for each connection
     *
     * @var resource[]
     */
    private $resources;

    /**
     * Instance of current cluster
     *
     * @var Cluster
     */
    private $cluster;

    /**
     * Name of the request class
     *
     * @var string
     */
    private $requestClass;

    /**
     * Name of the response class
     *
     * @var string
     */
    private $responseClass;

    public function __construct(Cluster $cluster, $requestClass, $responseClass, Stream ...$streams)
    {
        $this->streams = $streams;
        foreach ($streams as $stream) {
            $stream->joinGroup($this);
        }
        $this->cluster       = $cluster;
        $this->requestClass  = $requestClass;
        $this->responseClass = $responseClass;
    }

    public function registerHandle(Stream $stream, $resource)
    {
        $index = array_search($stream, $this->streams);
        if ($index === false) {
            throw new \InvalidArgumentException('Unknown stream registration');
        }
        $this->resources[$index] = $resource;
    }

    public function request(array $topicPartitionData, ...$requestArguments)
    {
        $requestByNode = [];

        // We group all requests for each node by looking at partition leader
        foreach ($topicPartitionData as $topic => $partitions) {
            foreach ($partitions as $partition => $partitionData) {
                $leaderNode = $this->cluster->leaderFor($topic, $partition);
                $requestByNode[$leaderNode->nodeId][$topic][$partition] = $partitionData;
            }
        }

        $readNodeSockets = [];
        foreach ($requestByNode as $nodeId => $nodeTopicPartitions)
        {
            /** @var Record $request */
            $request = new $this->requestClass($nodeTopicPartitions, ...$requestArguments);

            //TODO: need to receive configuration from somewhere, or encapsulate all logic in the cluster
            $stream  = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);

            $streamIndex = array_search($stream, $this->resources);

            $readNodeSockets[$nodeId] = $this->resources[$streamIndex];
            $request->writeTo($stream);
        }

        $incompleteReads = $readNodeSockets;
        if (!isset($timeout)) {
            $timeout = $this->configuration[ConsumerConfig::REQUEST_TIMEOUT_MS];
        }
        $responses = [];
        while (!empty($incompleteReads)) {
            $readSelect  = $incompleteReads;
            $writeSelect = $exceptSelect = null;
            if (stream_select($readSelect, $writeSelect, $exceptSelect, intdiv($timeout, 1000), $timeout % 1000) > 0) {
                foreach ($readSelect as $resourceToRead) {
                    $nodeId             = array_search($resourceToRead, $readNodeSockets);
                    $connection         = $this->cluster->nodeById($nodeId)->getConnection($this->configuration);
                    $responses[$nodeId] = $responseClass::unpack($connection);
                }
                $incompleteReads = array_diff($incompleteReads, $readSelect);
            }
        }
    }
}
