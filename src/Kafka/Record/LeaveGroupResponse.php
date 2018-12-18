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

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode' => Scheme::TYPE_INT16
        ];
    }
}
