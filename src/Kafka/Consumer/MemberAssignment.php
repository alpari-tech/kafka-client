<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Consumer;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\TopicPartitions;
use Protocol\Kafka\Scheme;

/**
 * Consumer Groups: The format of the MemberAssignment field for consumer groups
 *
 * MemberAssignment => Version PartitionAssignment
 *   Version => int16
 *   PartitionAssignment => [Topic [Partition]]
 *     Topic => string
 *     Partition => int32
 *   UserData => bytes
 */
class MemberAssignment implements BinarySchemeInterface
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
     * @var TopicPartitions[]
     */
    public $topicPartitions = [];

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

    /**
     * MemberAssignment constructor.
     *
     * @param array|int[][] $topicPartitions Partition assignments per topic
     * @param int           $version         Optional version
     * @param string        $userData        Additional user data
     */
    public function __construct(array $topicPartitions = [], $version = 0, $userData = '')
    {
        $packedTopicAssignment = [];
        foreach ($topicPartitions as $topic => $partitions) {
            $packedTopicAssignment[$topic] = new TopicPartitions($topic, $partitions);
        }

        $this->topicPartitions = $packedTopicAssignment;
        $this->version         = $version;
        $this->userData        = $userData;
    }

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme()
    {
        return [
            'version'         => Scheme::TYPE_INT16,
            'topicPartitions' => ['topic' => TopicPartitions::class],
            'userData'        => Scheme::TYPE_BYTEARRAY,
        ];
    }
}
