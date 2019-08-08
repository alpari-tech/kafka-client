<?php
/*
 * This file is part of the Alpari Kafka client.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);


namespace Alpari\Kafka\Common;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;
use Alpari\Kafka\Stream;

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
     * @todo Move this method outside this class
     *
     * @return Stream
     */
    public function getConnection(array $configuration): Stream
    {
        if (!isset(self::$nodeConnections[$this->host][$this->port])) {
            $connection = new Stream\SocketStream("tcp://{$this->host}:{$this->port}", $configuration);

            self::$nodeConnections[$this->host][$this->port] = $connection;
        }

        return self::$nodeConnections[$this->host][$this->port];
    }
}
