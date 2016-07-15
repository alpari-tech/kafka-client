<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\ProduceResponsePartition;
use Protocol\Kafka\Record;

/**
 * Produce response object
 */
class ProduceResponse extends AbstractResponse
{
    /**
     * List of broker metadata info
     *
     * @var array|ProduceResponsePartition[]
     */
    public $topics;

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
     * @return ProduceResponsePartition
     */
    private static function unpackTopicPartitionInfo(&$binaryStreamBuffer)
    {
        $partition = new ProduceResponsePartition();
        list(
            $partition->partition,
            $partition->errorCode,
        ) = array_values(unpack("Npartition/nerrorCode", $binaryStreamBuffer));
        $binaryStreamBuffer = substr($binaryStreamBuffer, 6);
        if (PHP_INT_SIZE === 8) {
            $partition->offset = reset(unpack('J', $binaryStreamBuffer));
        } else {
            list (,$partition->offset) = array_values(unpack('NlowWord/NhighWord', $binaryStreamBuffer));
        }
        $binaryStreamBuffer = substr($binaryStreamBuffer, 8);

        return $partition;
    }
}
