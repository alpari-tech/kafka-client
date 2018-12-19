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
use Alpari\Kafka\DTO\FetchRequestTopic;
use Alpari\Kafka\DTO\FetchRequestTopicPartition;
use Alpari\Kafka\Scheme;

/**
 * Fetch API
 *
 * The fetch API is used to fetch a chunk of one or more logs for some topic-partitions. Logically one specifies the
 * topics, partitions, and starting offset at which to begin the fetch and gets back a chunk of messages. In general,
 * the return messages will have offsets larger than or equal to the starting offset. However, with compressed
 * messages, it's possible for the returned messages to have offsets smaller than the starting offset. The number of
 * such messages is typically small and the caller is responsible for filtering out those messages.
 *
 * Fetch requests follow a long poll model so they can be made to block for a period of time if sufficient data is not
 * immediately available.
 *
 * As an optimization the server is allowed to return a partial message at the end of the message set. Clients should
 * handle this case.
 *
 * One thing to note is that the fetch API requires specifying the partition to consume from. The question is how
 * should a consumer know what partitions to consume from? In particular how can you balance the partitions over a set
 * of consumers acting as a group so that each consumer gets a subset of partitions. We have done this assignment
 * dynamically using zookeeper for the scala and java client. The downside of this approach is that it requires a
 * fairly fat client and a zookeeper connection. We haven't yet created a Kafka API to allow this functionality to be
 * moved to the server side and accessed more conveniently. A simple consumer client can be implemented by simply
 * requiring that the partitions be specified in config, though this will not allow dynamic reassignment of partitions
 * should that consumer fail. We hope to address this gap in the next major release.
 *
 * Fetch Request (Version: 5) => replica_id max_wait_time min_bytes max_bytes isolation_level [topics]
 *   replica_id => INT32
 *   max_wait_time => INT32
 *   min_bytes => INT32
 *   max_bytes => INT32
 *   isolation_level => INT8
 *   topics => topic [partitions]
 *     topic => STRING
 *     partitions => partition fetch_offset log_start_offset max_bytes
 *       partition => INT32
 *       fetch_offset => INT64
 *       log_start_offset => INT64
 *       max_bytes => INT32
 *
 * @deprecated since 0.11.0.0
 */
class FetchRequest extends AbstractRequest
{
    /**
     * With READ_COMMITTED (isolation_level = 1), non-transactional and COMMITTED transactional records are visible.
     *
     * @see $isolationLevel
     */
    public const READ_COMMITTED = 1;

    /**
     * Using READ_UNCOMMITTED (isolation_level = 0) makes all records visible.
     *
     * @see $isolationLevel
     */
    public const READ_UNCOMMITTED = 0;

    /**
     * @inheritDoc
     */
    protected const VERSION = 5;

    /**
     * @var array
     */
    private $topicPartitions;

    /**
     * The max wait time is the maximum amount of time in milliseconds to block waiting if insufficient data is
     * available at the time the request is issued.
     *
     * @var int
     */
    private $maxWaitTime;

    /**
     * This is the minimum number of bytes of messages that must be available to give a response.
     *
     * If the client sets
     * this to 0 the server will always respond immediately, however if there is no new data since their last request
     * they will just get back empty message sets. If this is set to 1, the server will respond as soon as at least one
     * partition has at least 1 byte of data or the specified timeout occurs. By setting higher values in combination
     * with the timeout the consumer can tune for throughput and trade a little additional latency for reading only
     * large chunks of data (e.g. setting MaxWaitTime to 100 ms and setting MinBytes to 64k would allow the server to
     * wait up to 100ms to try to accumulate 64k of data before responding).
     *
     * @var integer
     */
    private $minBytes;

    /**
     * This setting controls the visibility of transactional records.
     *
     * Using READ_UNCOMMITTED (isolation_level = 0) makes all records visible.
     * With READ_COMMITTED (isolation_level = 1), non-transactional and COMMITTED transactional records are visible.
     *
     * To be more concrete, READ_COMMITTED returns all data from offsets smaller than the current LSO (last stable
     * offset), and enables the inclusion of the list of aborted transactions in the result, which allows consumers to
     * discard ABORTED transactional records
     *
     * @since 0.11.0.0
     *
     * @var integer
     */
    private $isolationLevel;

    /**
     * Maximum bytes to accumulate in the response.
     *
     * Note that this is not an absolute maximum, if the first message in the first non-empty partition of the
     * fetch is larger than this value, the message will still be returned to ensure that progress can be made.
     *
     * This value previously was only in partition.max_bytes property, now it packed into own field too
     *
     * @since 0.10.1.0
     *
     * @var integer
     */
    private $maxBytes;

    /**
     * The replica id indicates the node id of the replica initiating this request. Normal client consumers should
     * always specify this as -1 as they have no node id. Other brokers set this to be their own node id. The value -2
     * is accepted to allow a non-broker to issue fetch requests as if it were a replica broker for debugging purposes.
     *
     * @var int
     */
    private $replicaId;

    public function __construct(
        array $topicPartitions,
        int $maxWaitTime,
        int $minBytes,
        int $maxBytes,
        int $isolationLevel = self::READ_UNCOMMITTED,
        int $replicaId = -1,
        string $clientId = '',
        int $correlationId = 0
    ) {
        foreach ($topicPartitions as $topic => $partitionOffset) {
            $partitions = [];
            foreach ($partitionOffset as $partition => $offset) {
                $partitions[$partition] = new FetchRequestTopicPartition($partition, $offset, $maxBytes);
            }
            $this->topicPartitions[$topic] = new FetchRequestTopic($topic, $partitions);
        }

        $this->maxWaitTime    = $maxWaitTime;
        $this->minBytes       = $minBytes;
        $this->maxBytes       = $maxBytes;
        $this->isolationLevel = $isolationLevel;
        $this->replicaId      = $replicaId;

        parent::__construct(Kafka::FETCH, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'replicaId'       => Scheme::TYPE_INT32,
            'maxWaitTime'     => Scheme::TYPE_INT32,
            'minBytes'        => Scheme::TYPE_INT32,
            'maxBytes'        => Scheme::TYPE_INT32,
            'isolationLevel'  => Scheme::TYPE_INT8,
            'topicPartitions' => ['topic' => FetchRequestTopic::class]
        ];
    }
}
