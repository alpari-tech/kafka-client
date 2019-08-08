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


namespace Alpari\Kafka\Record;

use Alpari\Kafka;
use Alpari\Kafka\Common\Node;
use Alpari\Kafka\Common\TopicMetadata;
use Alpari\Kafka\Scheme;

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

    /**
     * @inheritdoc
     */
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
