<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka;

/**
 * Kafka record class
 */
abstract class AbstractRecord implements BinarySchemeInterface
{

    /**
     * The message_size field gives the size of the subsequent request or response message in bytes.
     *
     * The client can read requests by first reading this 4 byte size as an integer N, and then reading and parsing
     * the subsequent N bytes of the request.
     *
     * @var integer
     */
    protected $messageSize = 0;

    /**
     * Unpacks the message from the binary data buffer
     *
     * @param Stream $stream Binary stream buffer
     *
     * @return static
     */
    final public static function unpack(Stream $stream)
    {
        return Scheme::readObjectFromStream(static::class, $stream);
    }

    /**
     * Writes the message to the stream
     *
     * @param Stream $stream Binary stream buffer
     */
    final public function writeTo(Stream $stream)
    {
        Scheme::writeObjectToStream($this, $stream);
    }
}
