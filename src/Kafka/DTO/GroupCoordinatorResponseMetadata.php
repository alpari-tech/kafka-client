<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * GroupCoordinator response data
 */
class GroupCoordinatorResponseMetadata
{
    /**
     * The broker id.
     *
     * @var integer
     */
    public $nodeId;

    /**
     * The hostname of the broker.
     *
     * @var string
     */
    public $host;

    /**
     * The port on which the broker accepts requests.
     *
     * @var integer
     */
    public $port;

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $coordinatorMetadata = new static();
        list(
            $coordinatorMetadata->nodeId,
            $hostLength
        ) = array_values($stream->read("NnodeId/nhostLength"));

        list(
            $coordinatorMetadata->host,
            $coordinatorMetadata->port
        ) = array_values($stream->read("a{$hostLength}host/Nport"));

        return $coordinatorMetadata;
    }
}
