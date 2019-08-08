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
use Alpari\Kafka\Stream\StringStream;

/**
 * Fetch response topic partition header
 *
 * partition_header => partition error_code high_watermark last_stable_offset log_start_offset [aborted_transactions]
 *   partition => INT32
 *   error_code => INT16
 *   high_watermark => INT64
 *   last_stable_offset => INT64
 *   log_start_offset => INT64
 *   aborted_transactions => producer_id first_offset
 *     producer_id => INT64
 *     first_offset => INT64
 */
class FetchResponsePartition implements BinarySchemeInterface
{
    /**
     * The id of the partition this response is for.
     *
     * @var integer
     */
    public $partition;

    /**
     * The error from this partition, if any.
     *
     * Errors are given on a per-partition basis because a given partition may be unavailable or maintained on a
     * different host, while others may have successfully accepted the produce request.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * The offset at the end of the log for this partition. This can be used by the client to determine how many
     * messages behind the end of the log they are.
     *
     * @var integer
     */
    public $highWaterMarkOffset;

    /**
     * The last stable offset (or LSO) of the partition.
     *
     * This is the last offset such that the state of all transactional records prior to this offset have been decided
     * (ABORTED or COMMITTED)
     *
     * @since version 4
     *
     * @var integer
     */
    public $lastStableOffset;

    /**
     * Earliest available offset.
     *
     * @since version 5
     *
     * @var integer
     */
    public $logStartOffset;

    /**
     * List of aborted transactions
     *
     * @since version 4
     *
     * @var FetchResponseAbortedTransaction[]
     */
    public $abortedTransactions = [];

    /**
     * @var string
     */
    public $recordBatchBuffer;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition'           => Scheme::TYPE_INT32,
            'errorCode'           => Scheme::TYPE_INT16,
            'highWaterMarkOffset' => Scheme::TYPE_INT64,
            'lastStableOffset'    => Scheme::TYPE_INT64,
            'logStartOffset'      => Scheme::TYPE_INT64,
            'abortedTransactions' => ['producerId' => FetchResponseAbortedTransaction::class],
            // TODO: this should be actualy dynamic array of RecordBatch::class entities
            'recordBatchBuffer'   => Scheme::TYPE_BYTEARRAY
        ];
    }

    /**
     * Returns collection of RecordBatches
     *
     * TODO: is this possible somehow to do this on Scheme level?
     * @return RecordBatch[]
     */
    public function getRecordBatches(): array
    {
        $recordBatches = [];
        // TODO: Avoid creation of temporary string buffer, this should be implemented in reader directly
        $buffer = new StringStream($this->recordBatchBuffer);
        while (!$buffer->isEmpty()) {
            $recordBatches[] = Scheme::readObjectFromStream(RecordBatch::class, $buffer);
        }

        return $recordBatches;
    }
}
