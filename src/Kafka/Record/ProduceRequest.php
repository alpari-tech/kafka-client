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
use Alpari\Kafka\DTO\ProduceRequestPartition;
use Alpari\Kafka\DTO\ProduceRequestTopic;
use Alpari\Kafka\DTO\RecordBatch;
use Alpari\Kafka\Scheme;

/**
 * The produce API
 *
 * The produce API is used to send message sets to the server. For efficiency it allows sending message sets intended
 * for many topic partitions in a single request.
 *
 * The produce API uses the generic message set format, but since no offset has been assigned to the messages at the
 * time of the send the producer is free to fill in that field in any way it likes.
 *
 * Produce Request (Version: 3) => transactional_id acks timeout [topic_data]
 *   transactional_id => NULLABLE_STRING
 *   acks => INT16
 *   timeout => INT32
 *   topic_data => topic [data]
 *     topic => STRING
 *     data => partition record_set
 *       partition => INT32
 *       record_set => RECORDS
 */
class ProduceRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    protected const VERSION = 3;

    /**
     * The transactional ID of the producer.
     *
     * This is used to authorize transaction produce requests.
     * This can be null for non-transactional producers.
     *
     * @var string|null
     */
    private $transactionalId;

    /**
     * The number of acknowledgments the producer requires the leader to have received before considering a request
     * complete. Allowed values: 0 for no acknowledgments, 1 for only the leader and -1 for the full ISR.
     */
    private $requiredAcks;

    /**
     * The time to await a response in ms.
     */
    private $timeout;

    /**
     * @var ProduceRequestTopic[]
     */
    public $topicMessages;

    /**
     * ProduceRequest constructor.
     *
     * @param array  $topicMessages   List of messages in format: topic => [partition => [messages]]
     * @param int    $requiredAcks    This field indicates how many acknowledgements the servers should receive before
     *                                responding to the request.
     *                                If it is 0 the server will not send any response
     *                                (this is the only case where the server will not reply to a request).
     *                                If it is 1, the server will wait the data is written to the local log before
     *                                sending a response. If it is -1 the server will block until the message is
     *                                committed by all in sync replicas before sending a response.
     * @param string $transactionalId The transactional ID of the producer. This is used to authorize transaction
     *                                produce requests. This can be null for non-transactional producers.
     * @param int    $timeout         This provides a maximum time in milliseconds the server can await the receipt of
     *                                the number of acknowledgements in RequiredAcks.
     * @param string $clientId        Kafka client identifier
     * @param int    $correlationId   Correlation request ID (will be returned in the response)
     */
    public function __construct(
        array $topicMessages = [],
        int $requiredAcks = 1,
        ?string $transactionalId = null,
        int $timeout = 0,
        string $clientId = '',
        int $correlationId = 0
    ) {

        foreach ($topicMessages as $topic => $partitionMessages) {
            $partitions = [];
            foreach ($partitionMessages as $partition => $records) {
                $recordBatch            = new RecordBatch($records);
                $partitions[$partition] = new ProduceRequestPartition($partition, $recordBatch);
            }

            $this->topicMessages[$topic] = new ProduceRequestTopic($topic, $partitions);

        }

        $this->requiredAcks    = $requiredAcks;
        $this->transactionalId = $transactionalId;
        $this->timeout         = $timeout;

        parent::__construct(Kafka::PRODUCE, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'transactionalId' => Scheme::TYPE_NULLABLE_STRING,
            'requiredAcks'    => Scheme::TYPE_INT16,
            'timeout'         => Scheme::TYPE_INT32,
            'topicMessages'   => ['topic' => ProduceRequestTopic::class]
        ];
    }
}
