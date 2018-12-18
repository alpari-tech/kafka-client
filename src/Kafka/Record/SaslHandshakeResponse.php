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
 * SASL handshake response
 */
class SaslHandshakeResponse extends AbstractResponse implements BinarySchemeInterface
{

    /**
     * Array of mechanisms enabled in the server.
     *
     * @var array|string[]
     */
    public $enabledMechanisms = [];

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
            'errorCode'         => Scheme::TYPE_INT16,
            'enabledMechanisms' => [Scheme::TYPE_STRING]
        ];
    }
}
