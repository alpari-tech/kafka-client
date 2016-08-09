<?php
/**
 * @author Alexander.Lisachenko
 * @date   01.08.2016
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka\Common\Config as GeneralConfig;

/**
 * Consumer config enumeration class
 */
final class Config extends GeneralConfig
{
    /**
     * A unique string that identifies the consumer group this consumer belongs to.
     *
     * This property is required if the consumer uses either the group management functionality by using
     * subscribe(topic) or the Kafka-based offset management strategy.
     */
    const GROUP_ID = 'group.id';

    /**
     * The class name of the partition assignment strategy that the client will use to distribute partition ownership
     * amongst consumer instances when group management is used
     */
    const PARTITION_ASSIGNMENT_STRATEGY = 'partition.assignment.strategy';

    /**
     * The timeout used to detect failures when using Kafka's group management facilities.
     *
     * When a consumer's heartbeat is not received within the session timeout, the broker will mark the consumer as
     * failed and rebalance the group.
     *
     * Since heartbeats are sent only when poll() is invoked, a higher session timeout allows more time for message
     * processing in the consumer's poll loop at the cost of a longer time to detect hard failures. See also
     * max.poll.records for another option to control the processing time in the poll loop. Note that the value must be
     * in the allowable range as configured in the broker configuration by group.min.session.timeout.ms and
     * group.max.session.timeout.ms
     */
    const SESSION_TIMEOUT_MS = 'session.timeout.ms';

    /**
     * The minimum amount of data the server should return for a fetch request.
     *
     * If insufficient data is available the request will wait for that much data to accumulate before answering the
     * request. The default setting of 1 byte means that fetch requests are answered as soon as a single byte of data
     * is available or the fetch request times out waiting for data to arrive. Setting this to something greater than 1
     * will cause the server to wait for larger amounts of data to accumulate which can improve server throughput a bit
     * at the cost of some additional latency.
     */
    const FETCH_MIN_BYTES = 'fetch.min.bytes';

    /**
     * The maximum amount of time the server will block before answering the fetch request if there isn't sufficient
     * data to immediately satisfy the requirement given by fetch.min.bytes.
     */
    const FETCH_MAX_WAIT_MS = 'fetch.max.wait.ms';

    /**
     * The maximum amount of data per-partition the server will return.
     *
     * This size must be at least as large as the maximum message size the server allows or else it is possible for the
     * producer to send messages larger than the consumer can fetch. If that happens, the consumer can get stuck trying
     * to fetch a large message on a certain partition.
     */
    const MAX_PARTITION_FETCH_BYTES = 'max.partition.fetch.bytes';

    /**
     * What to do when there is no initial offset in Kafka or if the current offset does not exist any more on the
     * server (e.g. because that data has been deleted):
     *
     * earliest: automatically reset the offset to the earliest offset
     * latest: automatically reset the offset to the latest offset
     * none: throw exception to the consumer if no previous offset is found for the consumer's group
     * anything else: throw exception to the consumer.
     */
    const AUTO_OFFSET_RESET = 'auto.offset.reset';

    /**
     * The expected time between heartbeats to the consumer coordinator when using Kafka's group management facilities.
     *
     * Heartbeats are used to ensure that the consumer's session stays active and to facilitate rebalancing when new
     * consumers join or leave the group. The value must be set lower than session.timeout.ms, but typically should be
     * set no higher than 1/3 of that value. It can be adjusted even lower to control the expected time for normal
     * rebalances.
     */
    const HEARTBEAT_INTERVAL_MS = 'heartbeat.interval.ms';

    /**
     * If true the consumer's offset will be periodically committed after poll() operation.
     */
    const ENABLE_AUTO_COMMIT = 'enable.auto.commit';

    /**
     * The frequency in milliseconds that the consumer offsets are auto-committed to Kafka if enable.auto.commit is set
     * to true.
     */
    const AUTO_COMMIT_INTERVAL_MS = 'auto.commit.interval.ms';

    /**
     * This option controls the retention time for topic offset storage, set to -1 to use broker retention time setting
     */
    const OFFSET_RETENTION_MS = 'offset.retention.ms';


    const KEY_DESERIALIZER              = 'key.deserializer';
    const VALUE_DESERIALIZER            = 'value.deserializer';
    const EXCLUDE_INTERNAL_TOPICS       = 'exclude.internal.topics';
    const MAX_POLL_RECORDS              = 'max.poll.records';
    const RECEIVE_BUFFER_BYTES          = 'receive.buffer.bytes';
    const CHECK_CRCS                    = 'check.crcs';
}
