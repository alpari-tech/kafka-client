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
 * SASL handshake response
 */
class SaslHandshakeResponse extends AbstractResponse
{

    /**
     * Array of mechanisms enabled in the server.
     *
     * @var array|string[]
     */
    public $enabledMechanisms = [];

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
            $self->errorCode,
            $mechanismsNumber
        ) = array_values($stream->read('NcorrelationId/nerrorCode/NmechanismsNumber'));

        for ($i=0; $i<$mechanismsNumber; $i++) {
            $mechanismLength = $stream->read('nmechanismLength')['mechanismLength'];
            $mechanism       = $stream->read("a{$mechanismLength}mechanism")['mechanism'];

            $self->enabledMechanisms[] = $mechanism;
        }

        return $self;
    }
}
