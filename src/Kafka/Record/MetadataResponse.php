<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\BrokerMetadata;
use Protocol\Kafka\DTO\TopicMetadata;
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
     * @var array|BrokerMetadata[]
     */
    public $brokers = [];

    /**
     * List of topics
     *
     * @var array|TopicMetadata[]
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
            $brokerMetadata = BrokerMetadata::unpack($stream);

            $self->brokers[$brokerMetadata->nodeId] = $brokerMetadata;
        }
        $numberOfTopics = $stream->read('NnumberOfTopics')['numberOfTopics'];

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            $topicMetadata = TopicMetadata::unpack($stream);

            $self->topics[$topicMetadata->topic] = $topicMetadata;
        }
        return $self;
    }
}
