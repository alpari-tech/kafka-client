<?php
/**
 * @author Alexander.Lisachenko
 * @date   01.08.2016
 */

namespace Protocol\Kafka\Consumer;

/**
 * Consumer config enumeration class
 */
final class Config
{
    /**
     * A list of host/port pairs to use for establishing the initial connection to the Kafka cluster.
     */
    const BOOTSTRAP_SERVERS = 'bootstrap.servers';

    /**
     * An id string to pass to the server when making requests.
     *
     * The purpose of this is to be able to track the source of requests beyond just ip/port by allowing a logical
     * application name to be included in server-side request logging.
     */
    const CLIENT_ID = 'client.id';

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
     * The configuration controls the maximum amount of time the client will wait for the response of a request.
     *
     * If the response is not received before the timeout elapses the client will resend the request if necessary or
     * fail the request if retries are exhausted.
     */
    const REQUEST_TIMEOUT_MS = 'request.timeout.ms';

    /**
     * The expected time between heartbeats to the consumer coordinator when using Kafka's group management facilities.
     * 
     * Heartbeats are used to ensure that the consumer's session stays active and to facilitate rebalancing when new
     * consumers join or leave the group. The value must be set lower than session.timeout.ms, but typically should be
     * set no higher than 1/3 of that value. It can be adjusted even lower to control the expected time for normal
     * rebalances.
     */
    const HEARTBEAT_INTERVAL_MS = 'heartbeat.interval.ms';


    const KEY_DESERIALIZER              = 'key.deserializer';
    const VALUE_DESERIALIZER            = 'value.deserializer';
    const SSL_KEY_PASSWORD              = 'ssl.key.password';
    const SSL_KEYSTORE_LOCATION         = 'ssl.keystore.location';
    const SSL_KEYSTORE_PASSWORD         = 'ssl.keystore.password';
    const CONNECTIONS_MAX_IDLE_MS       = 'connections.max.idle.ms';
    const ENABLE_AUTO_COMMIT            = 'enable.auto.commit';
    const EXCLUDE_INTERNAL_TOPICS       = 'exclude.internal.topics';
    const MAX_POLL_RECORDS              = 'max.poll.records';
    const RECEIVE_BUFFER_BYTES          = 'receive.buffer.bytes';
    const SASL_MECHANISM                = 'sasl.mechanism';
    const SECURITY_PROTOCOL             = 'security.protocol';
    const SEND_BUFFER_BYTES             = 'send.buffer.bytes';
    const SSL_ENABLED_PROTOCOLS         = 'ssl.enabled.protocols';
    const SSL_PROTOCOL                  = 'ssl.protocol';
    const AUTO_COMMIT_INTERVAL_MS       = 'auto.commit.interval.ms';
    const CHECK_CRCS                    = 'check.crcs';
    const METADATA_MAX_AGE_MS           = 'metadata.max.age.ms';
    const RECONNECT_BACKOFF_MS          = 'reconnect.backoff.ms';
    const RETRY_BACKOFF_MS              = 'retry.backoff.ms';
}