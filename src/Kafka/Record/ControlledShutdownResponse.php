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


namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\ControlledShutdownResponsePartition;
use Protocol\Kafka\Scheme;

/**
 * Controlled shutdown response
 *
 * ControlledShutdown Response (Version: 0) => error_code [partitions_remaining]
 *   error_code => INT16
 *   partitions_remaining => topic partition
 *     topic => STRING
 *     partition => INT32
 */
class ControlledShutdownResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * The topic partitions that the broker still leads.
     *
     * @var ControlledShutdownResponsePartition[]
     */
    public $remainingTopicPartitions = [];

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode'                => Scheme::TYPE_INT16,
            'remainingTopicPartitions' => [ControlledShutdownResponsePartition::class]
        ];
    }
}
