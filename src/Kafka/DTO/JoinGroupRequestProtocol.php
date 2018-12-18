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


namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Consumer\Subscription;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream\StringStream;

/**
 * Join group request protocol DTO
 *
 * JoinGroupRequestProtocol => protocol_name protocol_metadata
 *   protocol_name => STRING
 *   protocol_metadata => BYTES
 */
class JoinGroupRequestProtocol implements BinarySchemeInterface
{
    /**
     * Name of the protocol
     *
     * @var string
     */
    public $name;

    /**
     * Protocol-specific metadata
     *
     * @var string
     */
    public $metadata;

    /**
     * Default initializer
     */
    public function __construct($name, Subscription $subscription)
    {
        // TODO: This should be on scheme-level
        $stringStream = new StringStream();
        Scheme::writeObjectToStream($subscription, $stringStream);

        $this->name     = $name;
        $this->metadata = $stringStream->getBuffer();
    }

    public static function getScheme(): array
    {
        return [
            'name'     => Scheme::TYPE_STRING,
            'metadata' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
