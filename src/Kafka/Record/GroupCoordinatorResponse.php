<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\GroupCoordinatorResponseMetadata;
use Protocol\Kafka\Record;

/**
 * Group coordinator response
 */
class GroupCoordinatorResponse extends AbstractResponse
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Host and port information for the coordinator for a consumer group.
     *
     * @var GroupCoordinatorResponseMetadata
     */
    public $coordinator;

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
        $coordinatorMetadata = new GroupCoordinatorResponseMetadata();
        list(
            $self->correlationId,
            $self->errorCode,
            $coordinatorMetadata->nodeId,
            $hostLength
        ) = array_values(unpack("NcorrelationId/nerrorCode/NnodeId/nhostLength", $data));
        $data = substr($data, 12);
        list(
            $coordinatorMetadata->host,
            $coordinatorMetadata->port
        ) = array_values(unpack("a{$hostLength}host/Nport", $data));
        $self->coordinator = $coordinatorMetadata;

        return $self;
    }
}
