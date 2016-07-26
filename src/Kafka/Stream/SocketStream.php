<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Stream;

class SocketStream extends AbstractStream
{

    /**
     * Internal socket
     *
     * @var resource
     */
    private $streamSocket;

    /**
     * Socket stream constructor.
     *
     * @param string $streamSocket Socket resource
     */
    public function __construct($streamSocket)
    {
        $this->streamSocket = $streamSocket;
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
                throw new NetworkException("Can not write to the stream");
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
                throw new NetworkException("Can not read from the stream");
            }
            $streamBuffer .= $result;
        }

        $arguments = unpack($format, $streamBuffer);

        return $arguments;
    }
}
