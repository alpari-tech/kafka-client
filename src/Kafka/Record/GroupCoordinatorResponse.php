<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\GroupCoordinatorResponseMetadata;
use Protocol\Kafka\Scheme;

/**
 * Group coordinator response
 */
class GroupCoordinatorResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Host and port information for the coordinator for a consumer group.
     *
     * @var GroupCoordinatorResponseMetadata
     */
    public $coordinator;

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode'   => Scheme::TYPE_INT16,
            'coordinator' => GroupCoordinatorResponseMetadata::class
        ];
    }
}
