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
 * Offsets response DTO
 *
 * OffsetsResponsePartition => partition error_code timestamp offset
 *   partition => INT32
 *   error_code => INT16
 *   timestamp => INT64
 *   offset => INT64
 */
class OffsetsResponsePartition implements BinarySchemeInterface
{
    /**
     * The partition this response entry corresponds to.
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
     * The timestamp associated with the returned offset
     *
     * @since 0.10.1
     *
     * @var integer
     */
    public $timestamp;

    /**
     * Found offset
     *
     * @var integer
     */
    public $offset;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'partition' => Scheme::TYPE_INT32,
            'errorCode' => Scheme::TYPE_INT16,
            'timestamp' => Scheme::TYPE_INT64,
            'offset'    => Scheme::TYPE_INT64,
        ];
    }
}
