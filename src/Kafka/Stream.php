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
     *
     * @return array List of unpacked arguments
     */
    public function read($format);

    /**
     * Reads a string from the stream
     *
     * @return string
     */
    public function readString();

    /**
     * Reads a byte array from the stream
     *
     * @return string
     */
    public function readByteArray();

    /**
     * Reads a varint value from the stream
     *
     * @return integer
     */
    public function readVarint();

    /**
     * Writes the string to the stream
     *
     * @param string $string
     */
    public function writeString($string);

    /**
     * Writes the byte array to the stream
     *
     * @param string $data Binary data
     */
    public function writeByteArray($data);

    /**
     * Checks whether we are actually connected to server
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Writes the varint value to the stream
     *
     * @param integer $value
     */
    public function writeVarint($value);

    /**
     * Writes the raw buffer into the stream as-is
     *
     * @param string $buffer
     */
    public function writeBuffer($buffer);

    /**
     * Checks if stream is empty
     *
     * @return bool
     */
    public function isEmpty();
}
