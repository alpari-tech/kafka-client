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


namespace Alpari\Kafka\Record;

use Alpari\Kafka;
use Alpari\Kafka\DTO\OffsetsRequestTopic;
use Alpari\Kafka\Scheme;

/**
 * Offsets API
 *
 * This API describes the valid offset range available for a set of topic-partitions. As with the produce and fetch
 * APIs requests must be directed to the broker that is currently the leader for the partitions in question. This can
 * be determined using the metadata API.
 *
 * The response contains the starting offset of each segment for the requested partition as well as the "log end
 * offset" i.e. the offset of the next message that would be appended to the given partition.
 *
 * ListOffsets Request (Version: 1) => replica_id [topics]
 *   replica_id => INT32
 *   topics => topic [partitions]
 *     topic => STRING
 *     partitions => partition timestamp
 *       partition => INT32
 *       timestamp => INT64
 */
class OffsetsRequest extends AbstractRequest
{
    /**
     * Special value for the offset of the next coming message
     */
    public const LATEST = -1;

    /**
     * Special value for receiving the earliest available offset
     */
    public const EARLIEST = -2;

    /**
     * @inheritDoc
     */
    protected const VERSION = 1;

    /**
     * @var array
     */
    private $topicPartitions;

    /**
     * The replica id indicates the node id of the replica initiating this request. Normal client consumers should
     * always specify this as -1 as they have no node id. Other brokers set this to be their own node id. The value -2
     * is accepted to allow a non-broker to issue fetch requests as if it were a replica broker for debugging purposes.
     */
    private $replicaId;

    public function __construct(
        array $topicPartitions,
        int $replicaId = -1,
        string $clientId = '',
        int $correlationId = 0
    ) {
        $packedTopicPartitions = [];
        foreach ($topicPartitions as $topic => $partitions) {
            $packedTopicPartitions[$topic] = new OffsetsRequestTopic($topic, $partitions);
        }
        $this->topicPartitions = $packedTopicPartitions;
        $this->replicaId       = $replicaId;

        parent::__construct(Kafka::OFFSETS, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'replicaId'       => Scheme::TYPE_INT32,
            'topicPartitions' => ['topic' => OffsetsRequestTopic::class]
        ];
    }
}
