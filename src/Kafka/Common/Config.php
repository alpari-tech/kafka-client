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

namespace Protocol\Kafka\Common;

use Protocol\Kafka\Enum\SecurityProtocol;
use Protocol\Kafka\Enum\SslProtocol;

/**
 * General config, suitable for both producer and consumer
 */
class Config
{
    protected static $generalConfiguration = [
        Config::BOOTSTRAP_SERVERS            => [],
        Config::CLIENT_ID                    => 'PHP/Kafka',
        Config::STREAM_PERSISTENT_CONNECTION => false,
        Config::STREAM_ASYNC_CONNECT         => false,
        Config::METADATA_MAX_AGE_MS          => 300000,
        Config::RECEIVE_BUFFER_BYTES         => 32768,
        Config::SEND_BUFFER_BYTES            => 131072,
        Config::SECURITY_PROTOCOL            => SecurityProtocol::PLAINTEXT,
        Config::SSL_PROTOCOL                 => SslProtocol::TLS,
        Config::SSL_CLIENT_CERT_LOCATION     => null,
        Config::SSL_KEY_PASSWORD             => null,
        Config::SSL_KEY_LOCATION             => null,

        Config::CONNECTIONS_MAX_IDLE_MS   => 540000,
        Config::REQUEST_TIMEOUT_MS        => 30000,
        Config::SASL_MECHANISM            => 'GSSAPI',
        Config::METADATA_FETCH_TIMEOUT_MS => 60000,
        Config::RECONNECT_BACKOFF_MS      => 50,
        Config::RETRY_BACKOFF_MS          => 100,
    ];

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

    /**
     * Location of Certificate Authority file on local filesystem which should be used to authenticate
     * the identity of the remote peer.
     */
    const SSL_CA_CERT_LOCATION           = 'ssl.ca.cert.location';

    /**
     * Protocol used to communicate with brokers. Valid values are: PLAINTEXT, SSL, SASL_PLAINTEXT, SASL_SSL.
     *
     * Implemented values: PLAINTEXT, SSL
     */
    const SECURITY_PROTOCOL             = 'security.protocol';

    /**
     * The SSL protocol used to generate the SSLContext. Default setting is TLS, which is fine for most cases.
     * Allowed values are TLS, TLSv1.1 and TLSv1.2. SSL, SSLv2 and SSLv3, but their usage is discouraged due
     * to known security vulnerabilities.
     */
    const SSL_PROTOCOL                  = 'ssl.protocol';

    /**
     * Path to local certificate file on filesystem. It must be a PEM encoded file which contains your
     * certificate and private key. It can optionally contain the certificate chain of issuers.
     * The private key also may be contained in a separate file specified by SSL_KEY_LOCATION.
     *
     * (PHP Only option)
     */
    const SSL_CLIENT_CERT_LOCATION      = 'ssl.client.cert.location';

    /**
     * The location of the private key file. This is optional for client and can be used for two-way
     * authentication for client.
     */
    const SSL_KEY_LOCATION              = 'ssl.key.location';

    /**
     * The password of the private key. This is optional for client.
     */
    const SSL_KEY_PASSWORD              = 'ssl.key.password';

    const CONNECTIONS_MAX_IDLE_MS       = 'connections.max.idle.ms';
    const SASL_MECHANISM                = 'sasl.mechanism';
    const SSL_ENABLED_PROTOCOLS         = 'ssl.enabled.protocols';
    const RECONNECT_BACKOFF_MS          = 'reconnect.backoff.ms';
    const RETRY_BACKOFF_MS              = 'retry.backoff.ms';

    /**
     * Returns default configuration
     *
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return self::$generalConfiguration;
    }
}
