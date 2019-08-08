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
use Alpari\Kafka\Scheme;

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
     */
    public $memberId;

    /**
     * Member-specific metadata
     */
    public $metadata;

    public function __construct(string $memberId, string $metadata)
    {
        $this->memberId = $memberId;
        $this->metadata = $metadata;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'memberId' => Scheme::TYPE_STRING,
            'metadata' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
