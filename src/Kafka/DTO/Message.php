<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * A message in kafka is a key-value pair with a small amount of associated metadata.
 */
class Message
{
    /**
     * The CRC is the CRC32 of the remainder of the message bytes.
     *
     * This is used to check the integrity of the message on the broker and consumer.
     *
     * @var integer
     */
    public $crc;

    /**
     * This is a version id used to allow backwards compatible evolution of the message binary format.
     *
     * The current value is 1.
     *
     * @var integer
     */
    public $magicByte = 1;

    /**
     * This byte holds metadata attributes about the message.
     *
     * The lowest 3 bits contain the compression codec used for the message.
     *
     * The fourth lowest bit represents the timestamp type. 0 stands for CreateTime and 1 stands for LogAppendTime. The
     * producer should always set this bit to 0. (since 0.10.0)
     *
     * All other bits should be set to 0.
     *
     * @var integer
     */
    public $attributes;

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

    public static function fromValue($value, $attributes = 0)
    {
        $message = new static();

        $message->value      = $value;
        $message->attributes = $attributes;

        return $message;
    }

    public static function fromKeyValue($key, $value, $attributes = 0)
    {
        $message = new static();

        $message->key        = $key;
        $message->value      = $value;
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
        $message = new static();
        list(
            $message->crc,
            $message->magicByte,
            $message->attributes,
            $keyLength
        ) = array_values($stream->read('Ncrc32/cmagicByte/cattributes/NkeyLength'));

        if ($keyLength === 0xFFFFFFFF) {
            $keyLength = 0;
        }

        list($message->key, $valueLength) = array_values($stream->read("a{$keyLength}/NvalueLength"));

        if ($valueLength === 0xFFFFFFFF) {
            $valueLength = 0;
        }
        $message->value = $stream->read("a{$valueLength}value")['value'];

        return $message;
    }

    public function __toString()
    {
        $keyLength   = isset($this->key) ? strlen($this->key) : -1;
        $valueLength = isset($this->value) ? strlen($this->value) : -1;

        $keyLengthFormat   = $keyLength > 0 ? "a{$keyLength}" : 'a0';
        $valueLengthFormat = $valueLength > 0 ? "a{$valueLength}" : 'a0';

        $payload = pack(
            "ccN{$keyLengthFormat}N{$valueLengthFormat}",
            $this->magicByte,
            $this->attributes,
            $keyLength,
            $this->key,
            $valueLength,
            $this->value
        );

        return pack('N', crc32($payload)) . $payload;
    }
}
