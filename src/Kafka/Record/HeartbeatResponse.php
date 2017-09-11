<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Heartbeat response
 */
class HeartbeatResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

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
            $self->errorCode
        ) = array_values($stream->read('NcorrelationId/nerrorCode'));

        return $self;
    }
}
