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

use Alpari\Kafka\DTO\JoinGroupResponseMember;
use Alpari\Kafka\Scheme;

/**
 * Join group response
 *
 * JoinGroup Response (Version: 0) => error_code generation_id group_protocol leader_id member_id [members]
 *   error_code => INT16
 *   generation_id => INT32
 *   group_protocol => STRING
 *   leader_id => STRING
 *   member_id => STRING
 *   members => member_id member_metadata
 *     member_id => STRING
 *     member_metadata => BYTES
 */
class JoinGroupResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * The generation of the consumer group.
     *
     * @var integer
     */
    public $generationId;

    /**
     * The group protocol selected by the coordinator
     *
     * @var string
     */
    public $groupProtocol;

    /**
     * The leader of the group
     *
     * @var string
     */
    public $leaderId;

    /**
     * The consumer id assigned by the group coordinator.
     *
     * @var string
     */
    public $memberId;

    /**
     * List of members of the group with metadata as value
     *
     * @var array
     */
    public $members = [];

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode'     => Scheme::TYPE_INT16,
            'generationId'  => Scheme::TYPE_INT32,
            'groupProtocol' => Scheme::TYPE_STRING,
            'leaderId'      => Scheme::TYPE_STRING,
            'memberId'      => Scheme::TYPE_STRING,
            'members'       => ['memberId' => JoinGroupResponseMember::class]
        ];
    }
}
