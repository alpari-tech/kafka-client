<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\FetchResponsePartition;
use Protocol\Kafka\DTO\MessageSet;
use Protocol\Kafka\Record;

/**
 * Fetch response object
 */
class FetchResponse extends AbstractResponse
{

    /**
     * Duration in milliseconds for which the request was throttled due to quota violation.
     * (Zero if the request did not violate any quota.)
     *
     * @var integer
     * @since Version 1 of protocol
     */
    public $throttleTimeMs;

    /**
     * List of fetch responses
     *
     * @var array|FetchResponsePartition[]
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
            $self->throttleTimeMs,
            $numberOfTopics,
        ) = array_values(unpack("NcorrelationId/NthrottleTimeMs/NnumberOfTopics", $data));
        $data = substr($data, 12);

        for ($topic=0; $topic<$numberOfTopics; $topic++) {
            list($topicLength) = array_values(unpack('ntopicLength', $data));
            $data = substr($data, 2);
            list(
                $topicName,
                $numberOfPartitions
            ) = array_values(unpack("a{$topicLength}/NnumberOfPartitions", $data));
            $data = substr($data, $topicLength + 4);

            for ($partition = 0; $partition < $numberOfPartitions; $partition++) {
                $topicMetadata = self::unpackTopicPartitionInfo($data);
                $self->topics[$topicName][$topicMetadata->partition] = $topicMetadata;
            }

        }

        return $self;
    }

    /**
     * Unpacks the information about topic partition
     *
     * @param string $binaryStreamBuffer Binary buffer
     *
     * @return FetchResponsePartition
     */
    private static function unpackTopicPartitionInfo(&$binaryStreamBuffer)
    {
        $partition = new FetchResponsePartition();
        list(
            $partition->partition,
            $partition->errorCode,
            $partition->highwaterMarkOffset,
            $messageSetSize
        ) = array_values(unpack("Npartition/nerrorCode/JhighwaterMarkOffset/NmessageSetSize", $binaryStreamBuffer));
        $messageSetBuffer   = substr($binaryStreamBuffer, 18, $messageSetSize);
        $binaryStreamBuffer = substr($binaryStreamBuffer, 18 + $messageSetSize);

        while (!empty($messageSetBuffer)) {
            $messageSet = MessageSet::unpack($messageSetBuffer);
            $partition->messageSet[] = $messageSet;
        }

        return $partition;
    }
}
