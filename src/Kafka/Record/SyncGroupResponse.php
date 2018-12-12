<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Consumer\MemberAssignment;
use Protocol\Kafka\Scheme;

/**
 * Sync group response
 *
 * SyncGroup Response (Version: 1) => throttle_time_ms error_code member_assignment
 *   throttle_time_ms => INT32
 *   error_code => INT16
 *   member_assignment => BYTES
 */
class SyncGroupResponse extends AbstractResponse implements BinarySchemeInterface
{

    /**
     * Duration in milliseconds for which the request was throttled due to quota violation
     *
     * (Zero if the request did not violate any quota)
     *
     * @var integer
     */
    public $throttleTimeMs;

    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Assigned data to the member
     *
     * @var MemberAssignment
     */
    public $memberAssignment;

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'throttleTimeMs'   => Scheme::TYPE_INT32,
            'errorCode'        => Scheme::TYPE_INT16,
            'memberAssignment' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
