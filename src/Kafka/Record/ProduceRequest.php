<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\MessageSet;
use Protocol\Kafka\Record;

/**
 * The produce API
 *
 * The produce API is used to send message sets to the server. For efficiency it allows sending message sets intended
 * for many topic partitions in a single request.
 *
 * The produce API uses the generic message set format, but since no offset has been assigned to the messages at the
 * time of the send the producer is free to fill in that field in any way it likes.
 */
class ProduceRequest extends AbstractRequest
{
    /**
     * @var integer
     */
    private $requiredAcks;

    /**
     * @var integer
     */
    private $timeout;

    /**
     * @var array
     */
    private $topicMessages;

    public function __construct(array $topicMessages, $requiredAcks = 1, $timeout = 0, $correlationId = 0, $clientId = '')
    {
        $this->requiredAcks  = $requiredAcks;
        $this->timeout       = $timeout;
        $this->topicMessages = $topicMessages;

        parent::__construct(Kafka::PRODUCE, $correlationId, $clientId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload = parent::packPayload();

        $totalTopics = count($this->topicMessages);
        $payload .= pack('nNN', $this->requiredAcks, $this->timeout, $totalTopics);
        foreach ($this->topicMessages as $topic => $partitions) {
            $topicLength = strlen($topic);
            $payload .= pack("na{$topicLength}N", $topicLength, $topic, count($partitions));
            foreach ($partitions as $partition => $messages) {
                $messageSetPayload = '';
                foreach ($messages as $message) {
                    $messageSet = MessageSet::fromMessage($message);
                    $messageSetPayload .= $messageSet;
                }
                $payload .= pack('NN', $partition, strlen($messageSetPayload));
                $payload .= $messageSetPayload;
            }
        }

        return $payload;
    }
}