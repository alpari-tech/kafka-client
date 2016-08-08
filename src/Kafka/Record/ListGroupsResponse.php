<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * List groups response
 */
class ListGroupsResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * List of groups as keys and current protocols as values
     *
     * @var array
     */
    public $groups = [];

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $self->errorCode,
            $groupNumber
        ) = array_values($stream->read('NcorrelationId/nerrorCode/NgroupNumber'));

        for ($groupIndex = 0; $groupIndex < $groupNumber; $groupIndex++) {
            $groupId  = $stream->readString();
            $protocol = $stream->readString();

            $self->groups[$groupId] = $protocol;
        }

        return $self;
    }
}
