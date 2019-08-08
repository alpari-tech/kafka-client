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


namespace Alpari\Kafka\Record;

use Alpari\Kafka\Scheme;

/**
 * Sync group response
 *
 * SyncGroup Response (Version: 1) => throttle_time_ms error_code member_assignment
 *   throttle_time_ms => INT32
 *   error_code => INT16
 *   member_assignment => BYTES
 */
class SyncGroupResponse extends AbstractResponse
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
     * @todo This should be implemented on scheme-level as MemberAssignment
     * @var string
     */
    public $memberAssignment;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'throttleTimeMs'   => Scheme::TYPE_INT32,
            'errorCode'        => Scheme::TYPE_INT16,
            'memberAssignment' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
