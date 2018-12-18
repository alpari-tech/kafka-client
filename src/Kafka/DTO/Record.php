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


namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

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
class Record implements BinarySchemeInterface
{
    /**
     * Length of this message
     *
     * @var integer
     */
    public $length = 0;

    /**
     * Record level attributes are presently unused.
     *
     * @var integer
     */
    public $attributes = 0;

    /**
     * The timestamp delta of the record in the batch.
     *
     * The timestamp of each Record in the RecordBatch is its 'TimestampDelta' + 'FirstTimestamp'.
     *
     * @var integer
     * @since Version 2 of Message structure
     */
    public $timestampDelta = 0;

    /**
     * The offset delta of the record in the batch.
     *
     * The offset of each Record in the Batch is its 'OffsetDelta' + 'FirstOffset'.
     *
     * @var integer
     *
     * @since Version 2 of Message (Record) structure
     */
    public $offsetDelta = 0;

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
     * @var Header[]
     */
    public $headers = [];

    public static function fromValue($value, $attributes = 0)
    {
        $message = new static();

        $message->value          = $value;
        $message->timestampDelta = (int) (microtime(true) * 1000 - $_SERVER['REQUEST_TIME_FLOAT'] * 1000);
        $message->attributes     = $attributes;
        $message->length         = Scheme::getObjectTypeSize($message) - 1; /* Varint 0 length always equal to 1 */;

        return $message;
    }

    public static function getScheme(): array
    {
        return [
            'length'         => Scheme::TYPE_VARINT_ZIGZAG,
            'attributes'     => Scheme::TYPE_INT8,
            'timestampDelta' => Scheme::TYPE_VARLONG_ZIGZAG,
            'offsetDelta'    => Scheme::TYPE_VARINT_ZIGZAG,
            'key'            => Scheme::TYPE_VARCHAR_ZIGZAG,
            'value'          => Scheme::TYPE_VARCHAR_ZIGZAG,
            'headers'        => ['key' => Header::class, Scheme::FLAG_VARARRAY => true]
        ];
    }
}
