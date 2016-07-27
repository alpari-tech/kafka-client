<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\OffsetFetchPartition;
use Protocol\Kafka\Record;

/**
 * OffsetCommit
 *
 * This api saves out the consumer's position in the stream for one or more partitions. In the scala API this happens
 * when the consumer calls commit() or in the background if "autocommit" is enabled. This is the position the consumer
 * will pick up from if it crashes before its next commit().
 */
class OffsetCommitRequest extends AbstractRequest
{
    /**
     * The consumer group id.
     *
     * @var string
     */
    private $consumerGroup;

    /**
     * @var array
     */
    private $topicPartitions;

    public function __construct($consumerGroup, array $topicPartitions, $correlationId = 0, $clientId = '')
    {
        $this->consumerGroup   = $consumerGroup;
        $this->topicPartitions = $topicPartitions;

        parent::__construct(Kafka::OFFSET_COMMIT, $correlationId, $clientId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload     = parent::packPayload();
        $groupLength = strlen($this->consumerGroup);
        $totalTopics = count($this->topicPartitions);

        $payload .= pack("na{$groupLength}N", $groupLength, $this->consumerGroup, $totalTopics);
        foreach ($this->topicPartitions as $topic => $partitions) {
            $topicLength = strlen($topic);
            $payload    .= pack("na{$topicLength}N", $topicLength, $topic, count($partitions));
            /** @var OffsetFetchPartition $partition */
            foreach ($partitions as $partition) {
                $payload .= (string) $partition;
            }
        }

        return $payload;
    }
}
