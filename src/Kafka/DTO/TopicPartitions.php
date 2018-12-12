<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Generic topic-partitions structure
 *
 * TopicPartitions => [Topic [Partition]]
 *   Topic => string
 *   Partition => int32
 */
class TopicPartitions implements BinarySchemeInterface
{

    /**
     * Name of the topic to assign
     *
     * @var string
     */
    public $topic;

    /**
     * List of partitions from the topic to assign
     *
     * @var array
     */
    public $partitions = [];

    /**
     * @inheritDoc
     */
    public function __construct($topic, array $partitions)
    {
        $this->topic      = $topic;
        $this->partitions = $partitions;
    }

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme()
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => [Scheme::TYPE_INT32]
        ];
    }
}
