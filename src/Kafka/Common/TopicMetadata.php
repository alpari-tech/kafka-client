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


namespace Alpari\Kafka\Common;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

/**
 * Topic metadata DTO
 */
class TopicMetadata implements BinarySchemeInterface
{
    use RestorableTrait;

    /**
     * The error code for the given topic.
     *
     * @var integer
     */
    public $topicErrorCode;

    /**
     * The name of the topic
     *
     * @var string
     */
    public $topic;

    /**
     * Indicates if the topic is considered a Kafka internal topic
     *
     * @var boolean
     * @since Version 1 of protocol
     */
    public $isInternal;

    /**
     * Metadata for each partition of the topic.
     *
     * @var PartitionMetadata[]
     */
    public $partitions = [];

    public static function getScheme(): array
    {
        return [
            'topicErrorCode' => Scheme::TYPE_INT16,
            'topic'          => Scheme::TYPE_STRING,
            'isInternal'     => Scheme::TYPE_INT8,
            'partitions'     => [PartitionMetadata::class]
        ];
    }
}
