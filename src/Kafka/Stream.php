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


namespace Alpari\Kafka;

interface Stream
{
    /**
     * Writes arguments to the stream
     *
     * @param string $format       Format for packing arguments
     * @param array  ...$arguments List of arguments for packing
     *
     * @see pack() manual for format
     */
    public function write(string $format, ...$arguments): void;

    /**
     * Reads information from the stream, advance internal stream pointer
     *
     * @return array List of unpacked arguments
     * @see unpack() manual for format
     */
    public function read(string $format): array;

    /**
     * Reads a string from the stream
     */
    public function readString(): string;

    /**
     * Reads a byte array from the stream
     *
     * @return string|null
     */
    public function readByteArray(): ?string;

    /**
     * Reads a varint value from the stream
     */
    public function readVarint(): int;

    /**
     * Writes the string to the stream
     *
     * @param string $string
     */
    public function writeString(string $string): void;

    /**
     * Writes the byte array to the stream
     *
     * @param string $data Binary data
     */
    public function writeByteArray(?string $data): void;

    /**
     * Checks whether we are actually connected to server
     */
    public function isConnected(): bool;

    /**
     * Writes the varint value to the stream
     *
     * @param int $value
     */
    public function writeVarint(int $value): void;

    /**
     * Writes the raw buffer into the stream as-is
     *
     * @param string|null $buffer
     */
    public function writeBuffer(?string $buffer): void;

    /**
     * Checks if stream is empty
     */
    public function isEmpty(): bool;
}
