<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\OffsetFetchPartition;
use Protocol\Kafka\Record;

/**
 * Produce response object
 */
class OffsetFetchResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var array|OffsetFetchPartition[]
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
            $numberOfTopics,
        ) = array_values(unpack("NcorrelationId/NnumberOfTopics", $data));
        $data = substr($data, 8);

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
     * @return OffsetFetchPartition
     */
    private static function unpackTopicPartitionInfo(&$binaryStreamBuffer)
    {
        $partition = new OffsetFetchPartition();
        list(
            $partition->partition,
        ) = array_values(unpack("Npartition", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 4);
        if (PHP_INT_SIZE === 8) {
            $partition->offset = reset(unpack('J', $binaryStreamBuffer));
        } else {
            list (, $partition->offset) = array_values(unpack('NlowWord/NhighWord', $binaryStreamBuffer));
        }
        $binaryStreamBuffer = substr($binaryStreamBuffer, 8);
        list($metadataLength) = array_values(unpack('nmetadataLength', $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 2);
        $metadataLength     = $metadataLength < 0x8000 ? $metadataLength : 0;
        list(
            $partition->metadata,
            $partition->errorCode
        ) = array_values(unpack("a{$metadataLength}metadata/nerrorCode", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, $metadataLength + 2);

        return $partition;
    }
}
