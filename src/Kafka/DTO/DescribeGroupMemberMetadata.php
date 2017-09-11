<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

/**
 * DescribeGroup member metadata DTO
 */
class DescribeGroupMemberMetadata
{

    /**
     * 	The memberId assigned by the coordinator
     *
     * @var string
     */
    public $memberId;

    /**
     * The client id used in the member's latest join group request
     *
     * @var string
     */
    public $clientId;

    /**
     * The client host used in the request session corresponding to the member's join group.
     *
     * @var string
     */
    public $clientHost;

    /**
     * The metadata corresponding to the current group protocol in use (will only be present if the group is stable).
     *
     * @var string Binary data
     */
    public $memberMetadata;

    /**
     * The current assignment provided by the group leader (will only be present if the group is stable).
     *
     * @var string
     */
    public $memberAssignment;

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     *
     *  members => member_id client_id client_host member_metadata member_assignment
     *    member_id => STRING
     *    client_id => STRING
     *    client_host => STRING
     *    member_metadata => BYTES
     *    member_assignment => BYTES
     */
    public static function unpack(Stream $stream)
    {
        $memberMetadata = new static();

        $memberMetadata->memberId         = $stream->readString();
        $memberMetadata->clientId         = $stream->readString();
        $memberMetadata->clientHost       = $stream->readString();
        $memberMetadata->memberMetadata   = $stream->readByteArray();
        $memberMetadata->memberAssignment = $stream->readByteArray();

        return $memberMetadata;
    }
}
