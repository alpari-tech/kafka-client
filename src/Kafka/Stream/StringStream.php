<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka;
use Protocol\Kafka\Stream;
use Protocol\Kafka\StreamGroupRequest;

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
     * @param string $stringBuffer Buffer to write to or read from
     */
    public function __construct(&$stringBuffer)
    {
        $this->buffer = &$stringBuffer;
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
     * Joins current stream to the group (for stream_select)
     *
     * @param StreamGroupRequest $group Instance of group
     *
     * @return void
     */
    public function joinGroup(StreamGroupRequest $group)
    {
        $group->registerHandle($this, $this->buffer);
    }
}
