<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

/**
 * Fetch response partition header
 *
 * partition_header => partition error_code high_watermark last_stable_offset [aborted_transactions]
 *   partition => INT32
 *   error_code => INT16
 *   high_watermark => INT64
 *   last_stable_offset => INT64
 *   aborted_transactions => producer_id first_offset
 *     producer_id => INT64
 *     first_offset => INT64
 */
class FetchResponsePartition
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
     * List of aborted transactions
     *
     * @since version 4
     *
     * @var array Array where key is producer_id and value is the first offset in the aborted transaction
     */
    public $abortedTransactions = [];

    /**
     * @var RecordBatch
     */
    public $recordBatch;

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $partition = new static();
        list(
            $partition->partition,
            $partition->errorCode,
            $partition->highWaterMarkOffset,
            $partition->lastStableOffset,
            $numberOfAbortedTransactions
        ) = array_values($stream->read(
            'Npartition/' .
            'nerrorCode/' .
            'JhighWaterMarkOffset/' .
            'JlastStableOffset/' .
            'labortedTransactions' // Nullable array could contain -1 as value
        ));

        for ($abortedTransaction = 0; $abortedTransaction < $numberOfAbortedTransactions; $abortedTransaction++) {
            list ($producerId, $firstOffset) = array_values($stream->read('JproducerId/JfirstOffset'));
            $partition->abortedTransactions[$producerId] = $firstOffset;
        }

        // What to do with this size?
        $batchSize = $stream->read('NnumberOfRecords')['numberOfRecords'];

        $recordBatch            = RecordBatch::unpack($stream);
        $partition->recordBatch = $recordBatch;

        return $partition;
    }
}
