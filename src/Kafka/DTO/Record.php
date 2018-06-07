<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Common\Utils\ByteUtils;
use Protocol\Kafka\Stream;
use function strlen;

/**
 * A record in kafka is a key-value pair with a small amount of associated metadata.
 *
 * Record =>
 *   Length => Varint
 *   Attributes => Int8
 *   TimestampDelta => Varlong
 *   OffsetDelta => Varint
 *   Key => Bytes
 *   Value => Bytes
 *   Headers => [HeaderKey HeaderValue]
 *     HeaderKey => String
 *     HeaderValue => Bytes
 *
 * Note that in this schema, the Bytes and String types use a variable length integer to represent
 * the length of the field. The array type used for the headers also uses a Varint for the number of
 * headers.
 *
 * The current record attributes are depicted below:
 *
 *  ----------------
 *  | Unused (0-7) |
 *  ----------------
 *
 * The offset and timestamp deltas compute the difference relative to the base offset and
 * base timestamp of the batch that this record is contained in.
 *
 * @since 0.11.0
 */
class Record
{
    /**
     * Length of this message
     *
     * @var integer
     */
    public $length;

    /**
     * Record level attributes are presently unused.
     *
     * @var integer
     */
    public $attributes;

    /**
     * The timestamp delta of the record in the batch.
     *
     * The timestamp of each Record in the RecordBatch is its 'TimestampDelta' + 'FirstTimestamp'.
     *
     * @var integer
     * @since Version 2 of Message structure
     */
    public $timestampDelta;

    /**
     * The offset delta of the record in the batch.
     *
     * The offset of each Record in the Batch is its 'OffsetDelta' + 'FirstOffset'.
     *
     * @var integer
     *
     * @since Version 2 of Message (Record) structure
     */
    public $offsetDelta;

    /**
     * The key is an optional message key that was used for partition assignment. The key can be null.
     *
     * @var string
     */
    public $key;

    /**
     * The value is the actual message contents as an opaque byte array.
     *
     * Kafka supports recursive messages in which case this may itself contain a message set. The message can be null.
     *
     * @var string
     */
    public $value;

    /**
     * Application level record level headers.
     *
     * @since Version 2 of Message (Record) structure
     * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-82+-+Add+Record+Headers
     *
     * @var array
     */
    public $headers = [];

    public static function fromValue($value, $attributes = 0)
    {
        $message = new static();

        $message->value          = $value;
        $message->timestampDelta = (int) (microtime(true) * 1000 - $_SERVER['REQUEST_TIME_FLOAT'] * 1000);
        $message->length         = self::sizeOfBodyInBytes(
            $message->offsetDelta,
            $message->timestampDelta,
            $message->key,
            $message->value,
            $message->headers
        );
        $message->attributes     = $attributes;

        return $message;
    }

    public static function fromKeyValue($key, $value, $attributes = 0)
    {
        $message = new static();

        $message->key        = $key;
        $message->value      = $value;
        $message->timestamp  = microtime(true) * 1000;
        $message->attributes = $attributes;

        return $message;
    }

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $record                 = new static();
        $record->length         = $stream->readVarint();
        $record->attributes     = $stream->read('cattributes')['attributes'];
        $record->timestampDelta = $stream->readVarint();
        $record->offsetDelta    = $stream->readVarint();
        $keyLength              = $stream->readVarint();
        $record->key            = $stream->read("a{$keyLength}key")['key'];
        $valueLength            = $stream->readVarint();
        $record->value          = $stream->read("a{$valueLength}value")['value'];
        $headersNumber          = $stream->readVarint();
        for ($index = 0; $index < $headersNumber; $index++) {
            list($headerKey, $headerValue) = Header::unpack($stream);
            $record->headers[$headerKey] = $headerValue;
        }

        return $record;
    }


    public function pack(Stream $stream)
    {
        $stream->writeVarint($this->length);
        $stream->write('c', $this->attributes);
        $stream->writeVarint($this->timestampDelta);
        $stream->writeVarint($this->offsetDelta);
        $keyLength = isset($this->key) ? strlen($this->key) : 0;
        $stream->writeVarint($keyLength);
        $stream->write("a{$keyLength}", $this->key);
        $valueLength = isset($this->value) ? strlen($this->value) : 0;
        $stream->writeVarint($valueLength);
        $stream->write("a{$valueLength}", $this->value);
        $stream->writeVarint(count($this->headers));
        foreach ($this->headers as $key => $value) {
            Header::pack($stream, $key, $value);
        }
    }

    public static function sizeInBytes(
        $offsetDelta,
        $timestampDelta,
        $key,
        $value,
        array $headers
    ) {
        $bodySize = self::sizeOfBodyInBytes($offsetDelta, $timestampDelta, $key, $value, $headers);

        return ByteUtils::sizeOfVarint($bodySize) + $bodySize;
    }

    private static function sizeOfBodyInBytes(
        $offsetDelta,
        $timestampDelta,
        $key,
        $value,
        array $headers
    ) {
        $bodySize = 1; // always one byte for attributes
        $bodySize += ByteUtils::sizeOfVarint($offsetDelta);
        $bodySize += ByteUtils::sizeOfVarlong($timestampDelta);

        $keyLength = strlen($key);
        $bodySize  += ByteUtils::sizeOfVarint($keyLength) + $keyLength;

        $valueLength = strlen($value);
        $bodySize    += ByteUtils::sizeOfVarint($valueLength) + $valueLength;

        $bodySize += ByteUtils::sizeOfVarint(count($headers));
        foreach ($headers as $headerKey => $headerValue) {
            $headerLength = strlen($headerKey);
            $bodySize     += ByteUtils::sizeOfVarint($headerLength) + $headerLength;
            if ($headerValue === null) {
                $bodySize += ByteUtils::sizeOfVarint(-1);
            } else {
                $valueLength = strlen($headerValue);
                $bodySize    += ByteUtils::sizeOfVarint($valueLength) + $valueLength;
            }
        }

        return $bodySize;
    }
}
