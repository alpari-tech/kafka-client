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
 * SASL handshake response
 */
class SaslHandshakeResponse extends AbstractResponse
{

    /**
     * Array of mechanisms enabled in the server.
     *
     * @var string[]
     */
    public $enabledMechanisms = [];

    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode'         => Scheme::TYPE_INT16,
            'enabledMechanisms' => [Scheme::TYPE_STRING]
        ];
    }
}
