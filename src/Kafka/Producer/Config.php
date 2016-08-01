<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Producer;

/**
 * Producer config enumeration class
 */
final class Config
{
    const BOOTSTRAP_SERVERS         = 'bootstrap.servers';
    const KEY_SERIALIZER            = 'key.serializer';
    const VALUE_SERIALIZER          = 'value.serializer';
    const ACKS                      = 'acks';
    const BUFFER_MEMORY             = 'buffer.memory';
    const COMPRESSION_TYPE          = 'compression.type';
    const RETRIES                   = 'retries';
    const SSL_KEY_PASSWORD          = 'ssl.key.password';
    const SSL_KEYSTORE_LOCATION     = 'ssl.keystore.location';
    const SSL_KEYSTORE_PASSWORD     = 'ssl.keystore.password';
    const BATCH_SIZE                = 'batch.size';
    const CLIENT_ID                 = 'client.id';
    const CONNECTIONS_MAX_IDLE_MS   = 'connections.max.idle.ms';
    const LINGER_MS                 = 'linger.ms';
    const MAX_REQUEST_SIZE          = 'max.request.size';
    const PARTITIONER_CLASS         = 'partitioner.class';
    const RECEIVE_BUFFER_BYTES      = 'receive.buffer.bytes';
    const REQUEST_TIMEOUT_MS        = 'request.timeout.ms';
    const SASL_MECHANISM            = 'sasl.mechanism';
    const SECURITY_PROTOCOL         = 'security.protocol';
    const SEND_BUFFER_BYTES         = 'send.buffer.bytes';
    const TIMEOUT_MS                = 'timeout.ms';
    const METADATA_FETCH_TIMEOUT_MS = 'metadata.fetch.timeout.ms';
    const METADATA_MAX_AGE_MS       = 'metadata.max.age.ms';
    const RECONNECT_BACKOFF_MS      = 'reconnect.backoff.ms';
    const RETRY_BACKOFF_MS          = 'retry.backoff.ms';
}
