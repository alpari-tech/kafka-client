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


namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * ControlledShutdownResponsePartition DTO
 *
 * ControlledShutdownResponsePartition => partition timestamp
 *   topic => STRING
 *   partition => INT32
 */
class ControlledShutdownResponsePartition implements BinarySchemeInterface
{
    /**
     * Name of topic
     *
     * @var string
     */
    public $topic;

    /**
     * Topic partition id
     *
     * @var integer
     */
    public $partition;

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme(): array
    {
        return [
            'partition' => Scheme::TYPE_INT32,
            'timestamp' => Scheme::TYPE_INT64
        ];
    }
}
