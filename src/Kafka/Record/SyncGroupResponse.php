<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Sync group response
 */
class SyncGroupResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Assigned data to the member
     *
     * @var string
     */
    public $memberAssignment;

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * SyncGroupResponse => ErrorCode MemberAssignment
     *   ErrorCode => int16
     *   MemberAssignment => bytes
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $self->errorCode
        ) = array_values($stream->read('NcorrelationId/nerrorCode'));

        $self->memberAssignment = $stream->readByteArray();

        return $self;
    }
}
