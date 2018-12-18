<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Heartbeat response
 *
 * Heartbeat Response (Version: 0) => error_code
 *   error_code => INT16
 */
class HeartbeatResponse extends AbstractResponse implements BinarySchemeInterface
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
