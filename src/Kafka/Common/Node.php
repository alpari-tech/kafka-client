<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Common;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream;

/**
 * Information about a Kafka node
 */
class Node implements BinarySchemeInterface
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

    public static function getScheme(): array
    {
        return [
            'nodeId' => Scheme::TYPE_INT32,
            'host'   => Scheme::TYPE_STRING,
            'port'   => Scheme::TYPE_INT32,
            'rack'   => Scheme::TYPE_NULLABLE_STRING,
        ];
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
