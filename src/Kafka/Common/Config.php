<?php
/**
 * @author Alexander.Lisachenko
 * @date   01.08.2016
 */

namespace Protocol\Kafka\Common;

/**
 * General config, suitable for both producer and consumer
 */
class Config
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
     * The configuration controls the maximum amount of time the client will wait for the response of a request.
     *
     * If the response is not received before the timeout elapses the client will resend the request if necessary or
     * fail the request if retries are exhausted.
     */
    const REQUEST_TIMEOUT_MS = 'request.timeout.ms';

    /**
     * Should client use persistent connection to the cluster or not
     *
     * (PHP Only option)
     */
    const STREAM_PERSISTENT_CONNECTION = 'stream.persistent.connection';

    /**
     * Should client use asynchronous connection to the broker
     *
     * (PHP Only option)
     */
    const STREAM_ASYNC_CONNECT = 'stream.async.connect';

    /**
     * File name that stores the metadata, this file will be effectively cached by the Opcode cache in production
     *
     * (PHP Only option)
     */
    const METADATA_CACHE_FILE = 'metadata.cache.file';

    /**
     * The first time data is sent to the broker we must fetch metadata about that topic to know which servers host the
     * topic's partitions. This fetch to succeed before throwing an exception back to the client.
     */
    const METADATA_FETCH_TIMEOUT_MS = 'metadata.fetch.timeout.ms';

    /**
     * The period of time in milliseconds after which we force a refresh of metadata even if we haven't seen any
     * partition leadership changes to proactively discover any new brokers or partitions.
     *
     * Applied only if the metadata.cache.file is configured
     */
    const METADATA_MAX_AGE_MS = 'metadata.max.age.ms';

    /**
     * The size of the TCP send buffer (SO_SNDBUF) to use when sending data.
     */
    const SEND_BUFFER_BYTES = 'send.buffer.bytes';

    /**
     * The size of the TCP receive buffer (SO_RCVBUF) to use when reading data.
     */
    const RECEIVE_BUFFER_BYTES = 'receive.buffer.bytes';

    const SSL_KEY_PASSWORD              = 'ssl.key.password';
    const SSL_KEYSTORE_LOCATION         = 'ssl.keystore.location';
    const SSL_KEYSTORE_PASSWORD         = 'ssl.keystore.password';
    const CONNECTIONS_MAX_IDLE_MS       = 'connections.max.idle.ms';
    const SASL_MECHANISM                = 'sasl.mechanism';
    const SECURITY_PROTOCOL             = 'security.protocol';
    const SSL_ENABLED_PROTOCOLS         = 'ssl.enabled.protocols';
    const SSL_PROTOCOL                  = 'ssl.protocol';
    const RECONNECT_BACKOFF_MS          = 'reconnect.backoff.ms';
    const RETRY_BACKOFF_MS              = 'retry.backoff.ms';
}
