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
 *  JoinGroupResponseMember => member_id member_metadata
 *     member_id => STRING
 *     member_metadata => BYTES
 */
class JoinGroupResponseMember implements BinarySchemeInterface
{
    /**
     * Name of the group member
     *
     * @var string
     */
    public $memberId;

    /**
     * Member-specific metadata
     *
     * @var string
     */
    public $metadata;

    /**
     * Default initializer
     */
    public function __construct($memberId, $metadata)
    {
        $this->memberId = $memberId;
        $this->metadata = $metadata;
    }

    public static function getScheme()
    {
        return [
            'memberId' => Scheme::TYPE_STRING,
            'metadata' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
