<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

/**
 * The record batch structure is common to both the produce and fetch requests.
 *
 * A record batch is just a sequence of records with offset and size information.
 *
 * @since 0.11.0
 */
class RecordBatch
{
    /**
     * Offset used in kafka as the log sequence number.
     *
     * When the producer is sending non compressed messages, it can set the offsets to anything. When the producer is
     * sending compressed messages, to avoid server side recompression, each compressed message should have offset
     * starting from 0 and increasing by one for each inner message in the compressed message.
     *
     * @var integer
     */
    public $offset;

    /**
     * Size of the record data
     *
     * @var integer
     */
    public $messageSize;

    /**
     * Record information
     *
     * @var Record
     */
    public $message;

    public static function fromRecord(Record $message, $offset = 0)
    {
        $recordBatch = new static();

        $recordBatch->offset      = $offset;
        $recordBatch->message     = $message;
        $recordBatch->messageSize = strlen((string)$message);

        return $recordBatch;
    }

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $recordBatch = new static();
        list(
            $recordBatch->offset,
            $recordBatch->messageSize
        ) = array_values($stream->read('Joffset/NmessageSize'));

        $recordBatch->message = Record::unpack($stream);

        return $recordBatch;
    }

    public function __toString()
    {
        $message = (string)$this->message;
        $payload = pack(
            "JNa{$this->messageSize}",
            $this->offset,
            $this->messageSize,
            $message
        );

        return $payload;
    }
}
