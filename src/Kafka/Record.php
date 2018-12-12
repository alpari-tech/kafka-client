<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka;

use Protocol\Kafka;
use function is_a;
use RuntimeException;

/**
 * Kafka record class
 */
class Record
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
        if (is_a(static::class, BinarySchemeInterface::class, true)) {
            return Scheme::readObjectFromStream(static::class, $stream);
        }

        throw new RuntimeException('Implement BinarySchemeInterface for you class ' . static::class);
    }

    /**
     * Writes the message to the stream
     *
     * @param Stream $stream Binary stream buffer
     */
    final public function writeTo(Stream $stream)
    {
        if ($this instanceof BinarySchemeInterface) {
            Scheme::writeObjectToStream($this, $stream);

            return;
        }

        throw new RuntimeException('Implement BinarySchemeInterface for you class');
    }
}
