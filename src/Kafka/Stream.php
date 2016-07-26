<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka;

interface Stream
{
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
    public function write($format, ...$arguments);

    /**
     * Reads information from the stream, advanced internal pointer
     *
     * @param string $format Format for unpacking arguments
     * @see unpack() manual for format
     *partitionMetadata
     * @return array List of unpacked arguments
     */
    public function read($format);

    /**
     * Reads a string from the stream
     *partitionMetadata
     * @return string
     */
    public function readString();

    /**
     * Writes the string to the stream
     *
     * @param $string
     *
     * @return mixed
     */
    public function writeString($string);
}
