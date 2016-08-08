<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * DescribeGroup metadata DTO
 */
class DescribeGroupMetadata
{

    /**
     * Error code for the group
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Name of the group
     *
     * @var string
     */
    public $groupId;

    /**
     * The current state of the group
     * (one of: Dead, Stable, AwaitingSync, or PreparingRebalance, or empty if there is no active group)
     *
     * @var string
     */
    public $state;

    /**
     * The current group protocol type (will be empty if there is no active group)
     *
     * @var string
     */
    public $protocolType;

    /**
     * The current group protocol (only provided if the group is Stable)
     *
     * @var string
     */
    public $protocol;

    /**
     * Current group members (only provided if the group is not Dead)
     *
     * @var array
     */
    public $members = [];

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     * 
     * DescibeGroupMetadata => error_code group_id state protocol_type protocol [members]
     *   error_code => INT16
     *   group_id => STRING
     *   state => STRING
     *   protocol_type => STRING
     *   protocol => STRING
     *   members => member_id client_id client_host member_metadata member_assignment
     */
    public static function unpack(Stream $stream)
    {
        $groupMetadata = new static();

        $groupMetadata->errorCode    = $stream->read('nerrorCode')['errorCode'];
        $groupMetadata->groupId      = $stream->readString();
        $groupMetadata->state        = $stream->readString();
        $groupMetadata->protocolType = $stream->readString();
        $groupMetadata->protocol     = $stream->readString();

        $membersCount = $stream->read('NmembersCount')['membersCount'];
        for ($memberIndex = 0; $memberIndex < $membersCount; $memberIndex++) {
            $member = DescribeGroupMemberMetadata::unpack($stream);
            $groupMetadata->members[$member->memberId] = $member;
        }

        return $groupMetadata;
    }
}
