<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * Information about a Kafka node
 */
class Node
{
    use RestorableTrait;

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
     * The rack of the broker.
     *
     * @var string
     * @since Version 1 of protocol
     */
    public $rack;

    /**
     * Cached list of connections
     *
     * @var array
     */
    private static $nodeConnections = [];

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $brokerMetadata = new static();
        list($brokerMetadata->nodeId, $hostLength) = array_values($stream->read('NnodeId/nhostLength'));
        list(
            $brokerMetadata->host,
            $brokerMetadata->port
        ) = array_values($stream->read("a{$hostLength}host/Nport"));

        $brokerMetadata->rack = $stream->readString();

        return $brokerMetadata;
    }

    /**
     * Returns a connection to this node.
     *
     * @param array $configuration Client configuration
     *
     * @return Stream
     */
    public function getConnection(array $configuration)
    {
        if (!isset(self::$nodeConnections[$this->host][$this->port])) {
            $connection = new Stream\SocketStream("tcp://{$this->host}:{$this->port}", $configuration);

            self::$nodeConnections[$this->host][$this->port] = $connection;
        }

        return self::$nodeConnections[$this->host][$this->port];
    }
}
