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


namespace Alpari\Kafka\Producer;

use Alpari\Kafka\Common\Config as GeneralConfig;

/**
 * Producer config enumeration class
 */
final class Config extends GeneralConfig
{
    /**
     * Default configuration for producer (should be applied on top of default config)
     */
    protected static $producerConfiguration = [
        Config::PARTITIONER_CLASS => DefaultPartitioner::class,
        Config::ACKS              => 1,
        Config::TIMEOUT_MS        => 2000,
        Config::RETRIES           => 0,
        Config::BATCH_SIZE        => 0,
        Config::TRANSACTIONAL_ID  => null,

        Config::COMPRESSION_TYPE => 'none',
        Config::LINGER_MS        => 0,
        Config::MAX_REQUEST_SIZE => 1048576,
    ];

    /**
     * The number of acknowledgments the producer requires the leader to have received before considering a request
     * complete. This controls the durability of records that are sent. The following settings are common:
     *
     * acks=0 If set to zero then the producer will not wait for any acknowledgment from the server at all. The record
     * will be immediately added to the socket buffer and considered sent. No guarantee can be made that the server has
     * received the record in this case, and the retries configuration will not take effect (as the client won't
     * generally know of any failures). The offset given back for each record will always be set to -1.
     *
     * acks=1 This will mean the leader will write the record to its local log but will respond without awaiting full
     * acknowledgement from all followers. In this case should the leader fail immediately after acknowledging the
     * record but before the followers have replicated it then the record will be lost.
     *
     * acks=all This means the leader will wait for the full set of in-sync replicas to acknowledge the record. This
     * guarantees that the record will not be lost as long as at least one in-sync replica remains alive. This is the
     * strongest available guarantee.
     */
    public const ACKS = 'acks';

    /**
     * Partitioner class that implements the Partitioner interface.
     */
    public const PARTITIONER_CLASS = 'partitioner.class';

    /**
     * Setting a value greater than zero will cause the client to resend any record whose send fails with a potentially
     * transient error.
     *
     * Note that this retry is no different than if the client resent the record upon receiving the
     * error. Allowing retries without setting max.in.flight.requests.per.connection to 1 will potentially change the
     * ordering of records because if two batches are sent to a single partition, and the first fails and is retried
     * but the second succeeds, then the records in the second batch may appear first.
     */
    public const RETRIES = 'retries';

    /**
     * The producer will attempt to batch records together into fewer requests whenever multiple records are being sent
     * to the same partition. This helps performance on both the client and the server. This configuration controls the
     * default batch size in bytes.
     *
     * No attempt will be made to batch records larger than this size.
     *
     * Requests sent to brokers will contain multiple batches, one for each partition with data available to be sent.
     *
     * A small batch size will make batching less common and may reduce throughput (a batch size of zero will disable
     * batching entirely). A very large batch size may use memory a bit more wastefully as we will always allocate a
     * buffer of the specified batch size in anticipation of additional records.
     */
    public const BATCH_SIZE = 'batch.size';

    /**
     * The configuration controls the maximum amount of time the server will wait for acknowledgments from followers to
     * meet the acknowledgment requirements the producer has specified with the acks configuration. If the requested
     * number of acknowledgments are not met when the timeout elapses an error will be returned. This timeout is
     * measured on the server side and does not include the network latency of the request.
     */
    public const TIMEOUT_MS = 'timeout.ms';

    /**
     * The TransactionalId to use for transactional delivery.
     *
     * This enables reliability semantics which span multiple producer sessions since it allows the client to guarantee
     * that transactions using the same TransactionalId have been completed prior to starting any new transactions. If
     * no TransactionalId is provided, then the producer is limited to idempotent delivery. Note that
     * enable.idempotence must be enabled if a TransactionalId is configured. The default is empty, which means
     * transactions cannot be used.
     */
    public const TRANSACTIONAL_ID = 'transactional.id';

    public const COMPRESSION_TYPE = 'compression.type';
    public const LINGER_MS        = 'linger.ms';
    public const MAX_REQUEST_SIZE = 'max.request.size';

    /**
     * Returns default configuration for producer
     */
    public static function getDefaultConfiguration(): array
    {
        return self::$producerConfiguration + parent::$generalConfiguration;
    }
}
