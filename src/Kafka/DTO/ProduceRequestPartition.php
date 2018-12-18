<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use function pack;
use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Common\Utils\ByteUtils;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream\StringStream;
use function substr;

/**
 * Produce request Topic-Partition DTO
 */
class ProduceRequestPartition implements BinarySchemeInterface
{
    /**
     * The partition this request entry corresponds to.
     *
     * @var integer
     */
    public $partition;

    /**
     * Data for each separate partition in the topic
     *
     * @var string Should be RecordBatch, but not supported by scheme right now
     *
     * @todo Switch to the RecordBatch binary packet
     */
    public $recordBatch;

    /**
     * @inheritDoc
     */
    public function __construct($partition = 0, RecordBatch $recordBatch = null)
    {
        $this->partition = $partition;
        $recordBatch     = $recordBatch ?? new RecordBatch();

        $recordBatchStream = new StringStream();
        Scheme::writeObjectToStream($recordBatch, $recordBatchStream);
        $recordBatchBuffer = $recordBatchStream->getBuffer();

        // TODO: Calculation of CRC should be in the RecordBatch, but here we can work with raw buffer in one place
        $prefix = substr($recordBatchBuffer, 0, 17); // firstOffset..magic fields
        $body   = substr($recordBatchBuffer, 21);
        $crc32c = ByteUtils::crc32c($body);

        $recordBatch->crc  = $crc32c;
        $this->recordBatch = $prefix . pack('N', $crc32c) . $body;
    }

    public static function getScheme()
    {
        return [
            'partition'   => Scheme::TYPE_INT32,
            'recordBatch' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
