<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Produce request Topic DTO
 */
class ProduceRequestTopic implements BinarySchemeInterface
{
    /**
     * The name of the topic to produce to
     *
     * @var string
     */
    public $topic;

    /**
     * Data for all partitions in the topic
     *
     * @var ProduceRequestPartition[]
     */
    public $partitions = [];

    /**
     * @inheritDoc
     */
    public function __construct($topic = '', array $partitionData = [])
    {
        $this->topic      = $topic;
        $this->partitions = $partitionData;
    }

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => ProduceRequestPartition::class]
        ];
    }
}
