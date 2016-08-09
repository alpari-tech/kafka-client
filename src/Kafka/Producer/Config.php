<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Producer;

use Protocol\Kafka\Common\Config as GeneralConfig;

/**
 * Producer config enumeration class
 */
final class Config extends GeneralConfig
{
    const KEY_SERIALIZER            = 'key.serializer';
    const VALUE_SERIALIZER          = 'value.serializer';
    const ACKS                      = 'acks';
    const BUFFER_MEMORY             = 'buffer.memory';
    const COMPRESSION_TYPE          = 'compression.type';
    const RETRIES                   = 'retries';
    const BATCH_SIZE                = 'batch.size';
    const LINGER_MS                 = 'linger.ms';
    const MAX_REQUEST_SIZE          = 'max.request.size';
    const PARTITIONER_CLASS         = 'partitioner.class';
    const RECEIVE_BUFFER_BYTES      = 'receive.buffer.bytes';
    const TIMEOUT_MS                = 'timeout.ms';
    const METADATA_FETCH_TIMEOUT_MS = 'metadata.fetch.timeout.ms';
}
