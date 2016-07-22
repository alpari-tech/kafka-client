<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka;

use Protocol\Kafka;

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
    private $messageSize = 0;

    /**
     * The message_data field contains subsequent request or response message bytes.
     *
     * @var string
     */
    private $messageData = '';

    /**
     * Unpacks the message from the binary data buffer
     *
     * @param string $data Binary buffer with raw data
     *
     * @return static
     */
    final public static function unpack($data)
    {
        $self = new static();
        list($self->messageSize) = array_values(unpack(Kafka::HEADER_FORMAT, $data));

        $payload = substr($data, Kafka::HEADER_LEN);
        self::unpackPayload($self, $payload);
        if (static::class !== self::class && $self->messageSize > 0) {
            static::unpackPayload($self, $self->messageData);
        }

        return $self;
    }

    /**
     * Returns the binary message representation of record
     *
     * @return string
     */
    final public function __toString()
    {
        $headerPacket  = pack("N", $this->messageSize);
        return $headerPacket . $this->messageData;
    }

    /**
     * Sets the content data and adjusts the length fields
     *
     * @param $data
     */
    public function setMessageData($data)
    {
        $this->messageData = $data;
        $this->messageSize = strlen($this->messageData);
    }

    /**
     * Returns the context data from the record
     *
     * @return string
     */
    public function getMessageData()
    {
        return $this->messageData;
    }

    /**
     * Returns the size of content length
     *
     * @return int
     */
    final public function getMessageSize()
    {
        return $this->messageSize;
    }

    /**
     * Method to unpack the payload for the record.
     *
     * NB: Default implementation will be always called
     *
     * @param Record|static $self Instance of current frame
     * @param string $data Binary data
     */
    protected static function unpackPayload(Record $self, $data)
    {
        list($self->messageData) = array_values(unpack("a{$self->messageSize}contentData", $data));
    }

    /**
     * Implementation of packing the payload
     *
     * @return string
     */
    protected function packPayload()
    {
        return pack("a{$this->messageSize}", $this->messageData);
    }
}
