<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * Consumer Groups: The format of the MemberAssignment field for consumer groups
 */
class MemberAssignment
{

    /**
     * This is a version id.
     *
     * @var integer
     */
    public $version;

    /**
     * This property holds assignments of topic partitions for member.
     *
     * @var array
     */
    public $topicPartitions;

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

    public static function fromTopicPartitions(array $topicPartitions, $version = 0, $userData = '')
    {
        $message = new static();

        $message->topicPartitions = $topicPartitions;
        $message->version         = $version;
        $message->userData        = $userData;

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
            $topicPartitionsNumber
        ) = array_values($stream->read('nversion/NtopicNumber'));

        for ($topicIndex = 0; $topicIndex < $topicPartitionsNumber; $topicIndex++) {
            $topicName        = $stream->readString();
            $partitionsNumber = $stream->read('NpartitionsNumber')['partitionsNumber'];
            $partitions       = array_values($stream->read("N{$partitionsNumber}"));

            $message->topicPartitions[$topicName] = $partitions;
        }
        $message->userData = $stream->readByteArray();

        return $message;
    }

    /**
     * @return string
     *
     * MemberAssignment => Version PartitionAssignment
     *   Version => int16
     *   PartitionAssignment => [Topic [Partition]]
     *     Topic => string
     *     Partition => int32
     *   UserData => bytes
     */
    public function __toString()
    {
        $payload = pack('nN', $this->version, count($this->topicPartitions));
        foreach ($this->topicPartitions as $topic => $partitions) {
            $topicLength     = strlen($topic);
            $partitionsCount = count($partitions);
            $payload .= pack("na{$topicLength}N", $topicLength, $topic, $partitionsCount);
            $packArgs = $partitions;
            array_unshift($packArgs, "N{$partitionsCount}");
            $payload .= call_user_func_array('pack', $packArgs);
        }
        $payload .= pack('N', strlen($this->userData));
        $payload .= $this->userData;

        return $payload;
    }
}
