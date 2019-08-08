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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Consumer\Subscription;
use Alpari\Kafka\Scheme;
use Alpari\Kafka\Stream\StringStream;

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
     * Alpari-specific metadata
     *
     * @todo Update scheme to use Subscription instance directly
     * @var string
     */
    public $metadata;

    public function __construct(string $name, Subscription $subscription)
    {
        // TODO: This should be on scheme-level
        $stringStream = new StringStream();
        Scheme::writeObjectToStream($subscription, $stringStream);

        $this->name     = $name;
        $this->metadata = $stringStream->getBuffer();
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'name'     => Scheme::TYPE_STRING,
            'metadata' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
