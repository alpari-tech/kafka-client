<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Leave group response
 *
 * LeaveGroup Response (Version: 0) => error_code
 *   error_code => INT16
 */
class LeaveGroupResponse extends AbstractResponse implements BinarySchemeInterface
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode' => Scheme::TYPE_INT16
        ];
    }
}
