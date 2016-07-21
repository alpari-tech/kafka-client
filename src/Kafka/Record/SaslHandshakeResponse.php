<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

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
     * @param Record|static $self Instance of current frame
     * @param string $data Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, $data)
    {
        list(
            $self->correlationId,
            $self->errorCode,
            $mechanismsNumber
        ) = array_values(unpack("NcorrelationId/nerrorCode/NmechanismsNumber", $data));
        $data = substr($data, 10);

        for ($i=0; $i<$mechanismsNumber; $i++) {
            list($mechanismLength) = array_values(unpack("nmechanismLength", $data));
            $data = substr($data, 2);
            list($mechanism) = array_values(unpack("a{$mechanismLength}mechanism", $data));
            $data = substr($data, $mechanismLength);

            $self->enabledMechanisms[] = $mechanism;
        }

        return $self;
    }
}
