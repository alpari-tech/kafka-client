<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Common\Node;
use Protocol\Kafka\Common\TopicPartition;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Metadata response object
 */
class MetadataResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var array|Node[]
     */
    public $brokers = [];

    /**
     * The broker id of the controller broker.
     *
     * @var integer
     */
    public $controllerId;

    /**
     * List of topics
     *
     * @var array|TopicPartition[]
     */
    public $topics = [];

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $numberOfBrokers
        ) = array_values($stream->read('NcorrelationId/NnumberOfBrokers'));

        for ($broker=0; $broker<$numberOfBrokers; $broker++) {
            $brokerNode = Node::unpack($stream);

            $self->brokers[$brokerNode->nodeId] = $brokerNode;
        }
        $self->controllerId = $stream->read('NcontrollerId')['controllerId'];
        $numberOfTopics     = $stream->read('NnumberOfTopics')['numberOfTopics'];

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            $topicMetadata = TopicPartition::unpack($stream);

            $self->topics[$topicMetadata->topic] = $topicMetadata;
        }
        return $self;
    }
}
