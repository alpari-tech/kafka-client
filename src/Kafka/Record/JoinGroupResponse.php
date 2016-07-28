<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Join group response
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
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
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
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $self->errorCode,
            $self->generationId
        ) = array_values($stream->read('NcorrelationId/nerrorCode/NgenerationId'));

        $self->groupProtocol = $stream->readString();
        $self->leaderId      = $stream->readString();
        $self->memberId      = $stream->readString();

        $membersCount = $stream->read('NmembersCount')['membersCount'];
        for ($memberIndex = 0; $memberIndex < $membersCount; $memberIndex++) {
            $memberId   = $stream->readString();
            $memberData = $stream->readByteArray();
            $self->members[$memberId] = $memberData;
        }

        return $self;
    }
}
