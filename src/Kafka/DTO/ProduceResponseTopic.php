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
