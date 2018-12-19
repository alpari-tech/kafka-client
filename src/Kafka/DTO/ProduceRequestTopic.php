<?php
/*
 * This file is part of the Alpari Kafka client.
 *
 * (c) Alpari
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);


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
    public function __construct(string $topic, array $partitionData)
    {
        $this->topic      = $topic;
        $this->partitions = $partitionData;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => ['partition' => ProduceRequestPartition::class]
        ];
    }
}
