<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\Common\TopicMetadata;
use Protocol\Kafka\Scheme;

/**
 * Metadata response object
 */
class MetadataResponse extends AbstractResponse
{
    use Kafka\Common\RestorableTrait;

    /**
     * List of broker metadata info
     *
     * @var array|Node[]
     */
    public $brokers = [];

    /**
     * The cluster id that this broker belongs to.
     *
     * @since 0.10.1
     *
     * @var string
     */
    public $clusterId;

    /**
     * The broker id of the controller broker.
     *
     * @var integer
     * @since Version 1 of protocol
     */
    public $controllerId;

    /**
     * List of topics
     *
     * @var array|TopicMetadata[]
     */
    public $topics = [];

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'brokers'      => [Node::class],
            'clusterId'    => Scheme::TYPE_NULLABLE_STRING,
            'controllerId' => Scheme::TYPE_INT32,
            'topics'       => ['topic' => TopicMetadata::class],
        ];
    }
}
