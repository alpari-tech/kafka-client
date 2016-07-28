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
 * Controlled shutdown response
 */
class ControlledShutdownResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * The topic partitions that the broker still leads.
     *
     * @var array|string[]
     */
    public $remainingTopicPartitions = [];

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
            $topicPartitionsNumber
        ) = array_values($stream->read('NcorrelationId/nerrorCode/NtopicNumber'));

        for ($i=0; $i<$topicPartitionsNumber; $i++) {
            $topic     = $stream->readString();
            $partition = $stream->read('Npartition')['partition'];

            $self->remainingTopicPartitions[$topic][] = $partition;
        }

        return $self;
    }
}
