<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\BrokerMetadata;
use Protocol\Kafka\DTO\PartitionMetadata;
use Protocol\Kafka\DTO\TopicMetadata;
use Protocol\Kafka\Record;

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
     * @param Record|static $self Instance of current frame
     * @param string $data Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, $data)
    {
        list(
            $self->correlationId,
            $numberOfBrokers
        ) = array_values(unpack("NcorrelationId/NnumberOfBrokers", $data));
        $data = substr($data, 8);

        for ($broker=0; $broker<$numberOfBrokers; $broker++) {
            $brokerMetadata = self::unpackBrokerInfo($data);

            $self->brokers[$brokerMetadata->nodeId] = $brokerMetadata;
        }
        list($numberOfTopics) = array_values(unpack("NnumberOfTopics", $data));
        $data = substr($data, 4);

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            $topicMetadata = self::unpackTopicInfo($data);

            $self->topics[$topicMetadata->topic] = $topicMetadata;
        }
        return $self;
    }

    /**
     * Unpacks the information about broker
     *
     * @param Record|static $self Instance of current frame
     * @param string $binaryStreamBuffer Binary buffer
     *
     * @return BrokerMetadata
     */
    private static function unpackBrokerInfo(&$binaryStreamBuffer)
    {
        $brokerMetadata = new BrokerMetadata();
        list($brokerMetadata->nodeId, $hostLength) = array_values(unpack("NnodeId/nhostLength", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 6);
        list(
            $brokerMetadata->host,
            $brokerMetadata->port
        ) = array_values(unpack("a{$hostLength}host/Nport", $binaryStreamBuffer));

        $binaryStreamBuffer = substr($binaryStreamBuffer, $hostLength + 4);

        return $brokerMetadata;
    }

    /**
     * Unpacks the information about topic
     *
     * @param string $binaryStreamBuffer Binary buffer
     *
     * @return TopicMetadata
     */
    private static function unpackTopicInfo(&$binaryStreamBuffer)
    {
        $topic = new TopicMetadata();
        list(
            $topic->topicErrorCode,
            $topicLength
        ) = array_values(unpack("ntopicErrorCode/ntopicLength", $binaryStreamBuffer));

        $binaryStreamBuffer = substr($binaryStreamBuffer, 4);
        list(
            $topic->topic,
            $numberOfPartitions
        ) = array_values(unpack("a{$topicLength}topic/NnumberOfPartition", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, $topicLength + 4);

        for ($partition = 0; $partition < $numberOfPartitions; $partition++) {
            $partitionMetadata = self::unpackPartitionInfo($binaryStreamBuffer);

            $topic->partitions[$partitionMetadata->partitionId] = $partitionMetadata;
        }

        return $topic;
    }

    /**
     * Unpacks the information about partition
     *
     * @param string $binaryStreamBuffer Binary buffer
     *
     * @return PartitionMetadata
     */
    private static function unpackPartitionInfo(&$binaryStreamBuffer)
    {
        $partitionMetadata = new PartitionMetadata();
        list(
            $partitionMetadata->partitionErrorCode,
            $partitionMetadata->partitionId,
            $partitionMetadata->leader,
            $numberOfReplicas
        ) = array_values(unpack("npartitionErrorCode/NpartitionId/Nleader/NnumberOfReplicas", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 14);

        $partitionMetadata->replicas = array_values(unpack("N{$numberOfReplicas}", $binaryStreamBuffer));
        $binaryStreamBuffer          = substr($binaryStreamBuffer, 4 * $numberOfReplicas);

        list($numberOfIsr) = array_values(unpack("NnumberOfIsr", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 4);

        $partitionMetadata->isr = array_values(unpack("N{$numberOfIsr}", $binaryStreamBuffer));
        $binaryStreamBuffer     = substr($binaryStreamBuffer, 4 * $numberOfIsr);

        return $partitionMetadata;
    }
}
