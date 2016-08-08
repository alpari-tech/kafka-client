<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * Subscription information that is used for the synchronization between consumers
 */
class Subscription
{

    /**
     * This is a version id.
     *
     * @var integer
     */
    public $version;

    /**
     * This property holds all the topics for the consumer.
     *
     * @var array
     */
    public $topics;

    /**
     * The UserData field can be used by custom partition assignment strategies.
     *
     * For example, in a sticky partitioning implementation, this field can contain the assignment from the previous
     * generation. In a resource-based assignment strategy, it could include the number of cpus on the machine hosting
     * each consumer instance.
     *
     * @var string
     */
    public $userData;

    public static function fromSubscription(array $topics, $version = 0, $userData = '')
    {
        $message = new static();

        $message->topics   = $topics;
        $message->version  = $version;
        $message->userData = $userData;

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
            $message->version,
            $topicNumber
        ) = array_values($stream->read('nversion/NtopicNumber'));

        for ($topicIndex = 0; $topicIndex < $topicNumber; $topicIndex++) {
            $message->topics[] = $stream->readString();
        }
        $message->userData = $stream->readByteArray();

        return $message;
    }

    /**
     * @return string
     *
     * ProtocolMetadata => Version Subscription UserData
     *   Version => int16
     *   Subscription => [Topic]
     *     Topic => string
     *   UserData => bytes
     */
    public function __toString()
    {
        $payload = pack('nN', $this->version, count($this->topics));
        foreach ($this->topics as $topic) {
            $topicLength = strlen($topic);
            $payload .= pack("na{$topicLength}", $topicLength, $topic);
        }
        $userDataLength = strlen($this->userData);
        $payload .= pack('N', $userDataLength);
        $payload .= $this->userData;

        return $payload;
    }
}
