<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;

/**
 * The message set structure is common to both the produce and fetch requests.
 *
 * A message set is just a sequence of messages with offset and size information.
 *
 * This format happens to be used both for the on-disk storage on the broker and the on-the-wire format.
 */
class MessageSet
{
    /**
     * Offset used in kafka as the log sequence number.
     *
     * When the producer is sending non compressed messages, it can set the offsets to anything. When the producer is
     * sending compressed messages, to avoid server side recompression, each compressed message should have offset
     * starting from 0 and increasing by one for each inner message in the compressed message.
     *
     * @var integer
     */
    public $offset;

    /**
     * Size of the message data
     *
     * @var integer
     */
    public $messageSize;

    /**
     * Message information
     *
     * @var Message
     */
    public $message;

    public static function fromMessage(Message $message, $offset = 0)
    {
        $messageSet = new static();

        $messageSet->offset      = $offset;
        $messageSet->message     = $message;
        $messageSet->messageSize = strlen((string)$message);

        return $messageSet;
    }

    public function __toString()
    {
        $message = (string)$this->message;
        $payload = (PHP_INT_SIZE === 8) ? pack('J', $this->offset) : pack('NN', 0, $this->offset);
        $payload .= pack(
            "Na{$this->messageSize}",
            $this->messageSize,
            $message
        );

        return $payload;
    }
}
