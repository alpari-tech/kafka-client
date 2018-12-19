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
 * OffsetsRequestPartition DTO
 *
 * OffsetsRequestPartition => partition timestamp
 *   partition => INT32
 *   timestamp => INT64
 */
class OffsetsRequestPartition implements BinarySchemeInterface
{
    /**
     * Topic partition id
     */
    public $partition;

    /**
     * The target timestamp for the partition.
     */
    public $timestamp;

    /**
     * OffsetsRequestPartition constructor.
     *
     * @param int $partition Topic partition id
     * @param int $timestamp The target timestamp for the partition.
     */
    public function __construct(int $partition, int $timestamp)
    {
        $this->partition = $partition;
        $this->timestamp = $timestamp;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition' => Scheme::TYPE_INT32,
            'timestamp' => Scheme::TYPE_INT64
        ];
    }
}
