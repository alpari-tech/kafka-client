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
     * @param Stream $stream Binary stream buffer
     *
     * @return static
     */
    final public static function unpack(Stream $stream)
    {
        $self = new static();
        $self->messageSize = $stream->read(Kafka::HEADER_FORMAT)['size'];
        if ($self->messageSize > 0) {
            static::unpackPayload($self, $stream);
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
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        // nothing here
    }
}
