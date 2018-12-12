<?php
/**
 * @author Alexander.Lisachenko
 * @date   14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

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
    public function __construct($name, $metadata)
    {
        $this->name     = $name;
        $this->metadata = $metadata;
    }

    public static function getScheme()
    {
        return [
            'name'     => Scheme::TYPE_STRING,
            'metadata' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
