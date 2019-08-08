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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

/**
 * The record batch structure is common to both the produce and fetch requests.
 *
 * A record batch is just a sequence of records with offset and size information.
 *
 * RecordBatch implementation for magic 2 and above. The schema is given below:
 *
 * RecordBatch =>
 *   BaseOffset => Int64
 *   Length => Int32
 *   PartitionLeaderEpoch => Int32
 *   Magic => Int8
 *   CRC => Uint32
 *   Attributes => Int16
 *   LastOffsetDelta => Int32 // also serves as LastSequenceDelta
 *   FirstTimestamp => Int64
 *   MaxTimestamp => Int64
 *   ProducerId => Int64
 *   ProducerEpoch => Int16
 *   BaseSequence => Int32
 *   Records => [Record]
 *
 * Note that when compression is enabled (see attributes below), the compressed record data is serialized
 * directly following the count of the number of records.
 *
 * The CRC covers the data from the attributes to the end of the batch (i.e. all the bytes that follow the CRC). It is
 * located after the magic byte, which means that clients must parse the magic byte before deciding how to interpret
 * the bytes between the batch length and the magic byte. The partition leader epoch field is not included in the CRC
 * computation to avoid the need to recompute the CRC when this field is assigned for every batch that is received by
 * the broker. The CRC-32C (Castagnoli) polynomial is used for the computation.
 *
 * On Compaction: Unlike the older message formats, magic v2 and above preserves the first and last offset/sequence
 * numbers from the original batch when the log is cleaned. This is required in order to be able to restore the
 * producer's state when the log is reloaded. If we did not retain the last sequence number, then following
 * a partition leader failure, once the new leader has rebuilt the producer state from the log, the next sequence
 * expected number would no longer be in sync with what was written by the client. This would cause an
 * unexpected OutOfOrderSequence error, which is typically fatal. The base sequence number must be preserved for
 * duplicate checking: the broker checks incoming Produce requests for duplicates by verifying that the first and
 * last sequence numbers of the incoming batch match the last from that producer.
 *
 * Note that if all of the records in a batch are removed during compaction, the broker may still retain an empty
 * batch header in order to preserve the producer sequence information as described above. These empty batches
 * are retained only until either a new sequence number is written by the corresponding producer or the producerId
 * is expired from lack of activity.
 *
 * There is no similar need to preserve the timestamp from the original batch after compaction. The FirstTimestamp
 * field therefore always reflects the timestamp of the first record in the batch. If the batch is empty, the
 * FirstTimestamp will be set to -1 (NO_TIMESTAMP).
 *
 * Similarly, the MaxTimestamp field reflects the maximum timestamp of the current records if the timestamp type
 * is CREATE_TIME. For LOG_APPEND_TIME, on the other hand, the MaxTimestamp field reflects the timestamp set
 * by the broker and is preserved after compaction. Additionally, the MaxTimestamp of an empty batch always retains
 * the previous value prior to becoming empty.
 *
 * The current attributes are given below:
 *
 *  -------------------------------------------------------------------------------------------------
 *  | Unused (6-15) | Control (5) | Transactional (4) | Timestamp Type (3) | Compression Type (0-2) |
 *  -------------------------------------------------------------------------------------------------
 *
 * @since 0.11.0
 */
class RecordBatch implements BinarySchemeInterface
{

    /**
     * Denotes the first offset in the RecordBatch.
     *
     * The 'offsetDelta' of each Record in the batch would be be computed relative to this FirstOffset.
     * In particular, the offset of each Record in the Batch is its 'OffsetDelta' + 'FirstOffset'.
     */
    public $firstOffset;

    /**
     * Size of the record data
     */
    public $length;

    /**
     * Introduced with KIP-101, this is set by the broker upon receipt of a produce request and is used to ensure no
     * loss of data when there are leader changes with log truncation. Client developers do not need to worry about
     * setting this value.
     *
     * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-101+-+Alter+Replication+Alpari+to+use+Leader+Epoch+rather+than+High+Watermark+for+Truncation
     *
     * @since 0.11.0
     */
    public $partitionLeaderEpoch = -1;

    /**
     * The new message format has a Magic value of 2
     *
     * @since 0.11.0
     */
    public $magic = 2;

    /**
     * Control checksum for records
     *
     * @var integer
     */
    public $crc;

    /**
     * This byte holds metadata attributes about the message.
     *
     * The lowest 3 bits contain the compression codec used for the message.
     *
     * The fourth lowest bit represents the timestamp type. 0 stands for CreateTime and 1 stands for LogAppendTime.
     * The producer should always set this bit to 0. (since 0.10.0)
     *
     * The fifth lowest bit indicates whether the RecordBatch is part of a transaction or not. 0 indicates that the
     * RecordBatch is not transactional, while 1 indicates that it is. (since 0.11.0).
     *
     * The sixth lowest bit indicates whether the RecordBatch includes a control message. 1 indicates that the
     * RecordBatch is contains a control message, 0 indicates that it doesn't. Control messages are used to enable
     * transactions in Kafka and are generated by the broker. Clients should not return control batches (ie. those with
     * this bit set) to applications.
     *
     * @since 0.11.0
     */
    public $attributes;

    /**
     * The offset of the last message in the RecordBatch.
     *
     * This is used by the broker to ensure correct behavior even when Records within a batch are compacted out.
     *
     * @since 0.11.0
     */
    public $lastOffsetDelta;

    /**
     * The timestamp of the first Record in the batch.
     *
     * The timestamp of each Record in the RecordBatch is its 'TimestampDelta' + 'FirstTimestamp'.
     *
     * @since 0.11.0
     */
    public $firstTimestamp;

    /**
     * The timestamp of the last Record in the batch.
     *
     * This is used by the broker to ensure the correct behavior even when Records within the batch are compacted out.
     *
     * @since 0.11.0
     */
    public $maxTimestamp;

    /**
     * This is the broker assigned producerId received by the 'InitProducerId' request.
     *
     * Clients which want to support idempotent message delivery and transactions must set this field.
     *
     * @since 0.11.0
     * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-98+-+Exactly+Once+Delivery+and+Transactional+Messaging
     */
    public $producerId;

    /**
     * This is the broker assigned producerEpoch received by the 'InitProducerId' request.
     *
     * Clients which want to support idempotent message delivery and transactions must set this field.
     *
     * @since 0.11.0
     * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-98+-+Exactly+Once+Delivery+and+Transactional+Messaging
     */
    public $producerEpoch;

    /**
     * This is the producer assigned sequence number which is used by the broker to deduplicate messages.
     *
     * Clients which want to support idempotent message delivery and transactions must set this field.
     * The sequence number for each Record in the RecordBatch is its OffsetDelta + FirstSequence.
     *
     * @since 0.11.0
     * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-98+-+Exactly+Once+Delivery+and+Transactional+Messaging
     */
    public $firstSequence;

    /**
     * Batch of records
     *
     * @var Record[]
     */
    public $records;

    public function __construct(
        array $records = [],
        int $firstOffset = 0,
        int $partitionLeaderEpoch = -1,
        int $attributes = 0,
        int $lastOffsetDelta = 0,
        int $firstTimestamp = null,
        int $maxTimestamp = null,
        int $producerId = -1,
        int $producerEpoch = -1,
        int $firstSequence = -1
    ) {
        $milliSeconds = (int) (microtime(true) * 1e3);

        $this->records              = $records;
        $this->firstOffset          = $firstOffset;
        $this->firstSequence        = $firstSequence;
        $this->partitionLeaderEpoch = $partitionLeaderEpoch;
        $this->attributes           = $attributes;
        $this->lastOffsetDelta      = $lastOffsetDelta;
        $this->firstTimestamp       = $firstTimestamp ?? $milliSeconds;
        $this->maxTimestamp         = $maxTimestamp ?? $milliSeconds;
        $this->producerId           = $producerId;
        $this->producerEpoch        = $producerEpoch;

        // calculated fields
        $this->length = Scheme::getObjectTypeSize($this) - 12;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'firstOffset'          => Scheme::TYPE_INT64,
            'length'               => Scheme::TYPE_INT32,
            'partitionLeaderEpoch' => Scheme::TYPE_INT32,
            'magic'                => Scheme::TYPE_INT8,
            'crc'                  => Scheme::TYPE_INT32,
            'attributes'           => Scheme::TYPE_INT16,
            'lastOffsetDelta'      => Scheme::TYPE_INT32,
            'firstTimestamp'       => Scheme::TYPE_INT64,
            'maxTimestamp'         => Scheme::TYPE_INT64,
            'producerId'           => Scheme::TYPE_INT64,
            'producerEpoch'        => Scheme::TYPE_INT16,
            'firstSequence'        => Scheme::TYPE_INT32,
            'records'              => [Record::class]
        ];
    }
}
