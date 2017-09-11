<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;

/**
 * Offsets API
 *
 * This API describes the valid offset range available for a set of topic-partitions. As with the produce and fetch
 * APIs requests must be directed to the broker that is currently the leader for the partitions in question. This can
 * be determined using the metadata API.
 *
 * The response contains the starting offset of each segment for the requested partition as well as the "log end
 * offset" i.e. the offset of the next message that would be appended to the given partition.
 */
class OffsetsRequest extends AbstractRequest
{
    /**
     * Special value for the offset of the next coming message
     */
    const LATEST = -1;

    /**
     * Special value for receiving the earliest available offset
     */
    const EARLIEST = -2;

    /**
     * @var array
     */
    private $topicPartitions;


    /**
     * The replica id indicates the node id of the replica initiating this request. Normal client consumers should
     * always specify this as -1 as they have no node id. Other brokers set this to be their own node id. The value -2
     * is accepted to allow a non-broker to issue fetch requests as if it were a replica broker for debugging purposes.
     *
     * @var int
     */
    private $replicaId;

    /**
     * Maximum number of offsets to return
     *
     * @var int
     */
    private $maxOffsets;

    public function __construct(
        array $topicPartitions,
        $maxOffsets = 1,
        $replicaId = -1,
        $clientId = '',
        $correlationId = 0
    ) {
        $this->topicPartitions = $topicPartitions;
        $this->maxOffsets      = $maxOffsets;
        $this->replicaId       = $replicaId;

        parent::__construct(Kafka::OFFSETS, $clientId, $correlationId, Kafka::VERSION_0);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload     = parent::packPayload();
        $totalTopics = count($this->topicPartitions);

        $payload .= pack('NN', $this->replicaId, $totalTopics);
        foreach ($this->topicPartitions as $topic => $partitions) {
            $topicLength = strlen($topic);
            $payload .= pack("na{$topicLength}N", $topicLength, $topic, count($partitions));
            foreach ($partitions as $partitionId => $timeOffset) {
                $payload .= pack('NJN', $partitionId, $timeOffset, $this->maxOffsets);
            }
        }

        return $payload;
    }
}
