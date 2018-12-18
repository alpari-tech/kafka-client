<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Produce response Topic DTO
 */
class ProduceResponseTopic implements BinarySchemeInterface
{
    /**
     * The name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Data for all partitions in the topic
     *
     * @var ProduceResponsePartition[]
     */
    public $partitions = [];

    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => ProduceResponsePartition::class]
        ];
    }
}
