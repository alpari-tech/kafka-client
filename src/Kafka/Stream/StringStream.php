<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

class StringStream extends AbstractStream
{

    /**
     * Internal binary buffer
     *
     * @var string
     */
    private $buffer;

    /**
     * String stream constructor.
     *
     * @param string $stringBuffer Optional buffer to write to or read from
     */
    public function __construct($stringBuffer = null)
    {
        $this->buffer = $stringBuffer;
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
        $this->buffer .= pack($format, ...$arguments);
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
        $arguments    = unpack($format, $this->buffer);
        $this->buffer = substr($this->buffer, self::packetSize($format));

        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return true;
    }

    /**
     * Returns the current buffer, useful for write opertaions
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }


    /**
     * Checks if stream is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return strlen($this->buffer) === 0;
    }
}
