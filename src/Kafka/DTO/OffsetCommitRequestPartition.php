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
 * OffsetCommitRequestPartition DTO
 *
 * OffsetCommitRequestPartition => partition offset metadata
 *   partition => INT32
 *   offset => INT64
 *   metadata => NULLABLE_STRING
 */
class OffsetCommitRequestPartition implements BinarySchemeInterface
{
    /**
     * The partition this request entry corresponds to.
     */
    public $partition;

    /**
     * The offset assigned to the first message in the message set appended to this partition.
     */
    public $offset;

    /**
     * Any associated metadata the client wants to keep.
     */
    public $metadata;

    public function __construct(int $partition, int $offset, ?string $metadata = null)
    {
        $this->partition = $partition;
        $this->offset    = $offset;
        $this->metadata  = $metadata;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition' => Scheme::TYPE_INT32,
            'offset'    => Scheme::TYPE_INT64,
            'metadata'  => Scheme::TYPE_NULLABLE_STRING
        ];
    }
}
