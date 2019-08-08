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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

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
     */
    public $topic;

    /**
     * List of partitions from the topic to assign
     *
     * @var integer[]
     */
    public $partitions = [];

    /**
     * @inheritDoc
     */
    public function __construct(string $topic, array $partitions)
    {
        $this->topic      = $topic;
        $this->partitions = $partitions;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'topic'      => Scheme::TYPE_STRING,
            'partitions' => [Scheme::TYPE_INT32]
        ];
    }
}
