<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Stream;

/**
 * Implementation of simple socket stream
 */
class SocketStream extends AbstractStream
{
    /**
     * Internal socket
     *
     * @var resource
     */
    protected $streamSocket;

    /**
     * Host name
     *
     * @var string
     */
    protected $host;

    /**
     * Port number
     *
     * @var integer
     */
    protected $port;

    /**
     * Timeout for connection
     *
     * @var integer
     */
    protected $timeout;

    /**
     * Socket stream constructor
     *
     * @param string $tcpAddress Tcp address for connection
     * @param integer|null $connectionTimeout Connection timeout in seconds or null for using the default value
     */
    public function __construct($tcpAddress, $connectionTimeout = 1)
    {
        $tcpInfo = parse_url($tcpAddress);
        if ($tcpInfo === false || !isset($tcpInfo['host'])) {
            throw new NetworkException(['error' => "Malformed tcp address: {$tcpAddress}"]);
        }
        $this->host    = $tcpInfo['host'];
        $this->port    = isset($tcpInfo['port']) ? $tcpInfo['port'] : 9092;
        $this->timeout = isset($connectionTimeout) ? $connectionTimeout : ini_get("default_socket_timeout");

        $this->connect();
    }

    /**
     * Writes arguments to the stream
     *
     * @param string $format       Format for packing arguments
     * @param array  ...$arguments List of arguments for packing
     *
     * @see pack() manual for format
     *
     * @return void
     */
    public function write($format, ...$arguments)
    {
        $packedData = pack($format, ...$arguments);

        for ($written = 0; $written < strlen($packedData); $written += $result) {
            $result = fwrite($this->streamSocket, substr($packedData, $written));
            if ($result === false) {
                throw new NetworkException(['error' => 'Can not write to the stream']);
            }
        }
    }

    /**
     * Reads information from the stream, advanced internal pointer
     *
     * @param string $format Format for unpacking arguments
     * @see unpack() manual for format
     *
     * @return array List of unpacked arguments
     */
    public function read($format)
    {
        $packetSize   = self::packetSize($format);
        $streamBuffer = '';

        for ($received = 0; $received < $packetSize; $received += strlen($result)) {
            $result = fread($this->streamSocket, $packetSize);
            if ($result === false) {
                throw new NetworkException(['error' => 'Can not read from the stream']);
            }
            $streamBuffer .= $result;
        }

        $arguments = unpack($format, $streamBuffer);

        return $arguments;
    }

    /**
     * Automatic resource clean up
     */
    final public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Performs connection to the specified socket address
     */
    protected function connect()
    {
        $streamSocket = @fsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);
        if (!$streamSocket) {
            throw new NetworkException(compact('errorNumber', 'errorString'));
        }

        $this->streamSocket = $streamSocket;
    }

    /**
     * Performs the disconnect operation
     */
    protected function disconnect()
    {
        if (is_resource($this->streamSocket)) {
            fclose($this->streamSocket);
        }
    }
}
