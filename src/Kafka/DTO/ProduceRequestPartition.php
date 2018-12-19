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
use Alpari\Kafka\Common\Utils\ByteUtils;
use Alpari\Kafka\Scheme;
use Alpari\Kafka\Stream\StringStream;

/**
 * Produce request Topic-Partition DTO
 */
class ProduceRequestPartition implements BinarySchemeInterface
{
    /**
     * The partition this request entry corresponds to.
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
    public function __construct(int $partition = 0, RecordBatch $recordBatch = null)
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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition'   => Scheme::TYPE_INT32,
            'recordBatch' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
