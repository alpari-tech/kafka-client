<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

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
