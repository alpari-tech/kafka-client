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
use Protocol\Kafka\Consumer\MemberAssignment;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream\StringStream;

/**
 * SyncGroupRequest group member assignment
 *
 * GroupAssignment => [MemberId MemberAssignment]
 *   MemberId => string
 *   MemberAssignment => MemberAssignment
 */
class SyncGroupRequestMember implements BinarySchemeInterface
{
    /**
     * Name of the group member
     *
     * @var string
     */
    public $memberId;

    /**
     * Member-specific assignment
     *
     * @var string This field should be MemberAssignment instance
     */
    public $assignment;

    /**
     * Default initializer
     *
     * @param string $memberId Member identifier
     * @param MemberAssignment $assignment Received assignment
     */
    public function __construct($memberId, MemberAssignment $assignment)
    {
        $this->memberId = $memberId;
        // TODO: This should be done on scheme-level
        $stringBuffer = new StringStream();
        Scheme::writeObjectToStream($assignment, $stringBuffer);
        $this->assignment = $stringBuffer->getBuffer();
    }

    public static function getScheme(): array
    {
        return [
            'memberId'   => Scheme::TYPE_STRING,
            'assignment' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
