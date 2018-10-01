<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Kafka uses numeric codes to indicate what problem occurred on the server.
 *
 * These can be translated by the client into exceptions or whatever the appropriate error handling mechanism in the
 * client language.
 */
abstract class KafkaException extends \RuntimeException
{
    const UNKNOWN = -1;

    const NO_ERROR = 0;

    const OFFSET_OUT_OF_RANGE              = 1;
    const CORRUPT_MESSAGE                  = 2;
    const UNKNOWN_TOPIC_OR_PARTITION       = 3;
    const INVALID_FETCH_SIZE               = 4;
    const LEADER_NOT_AVAILABLE             = 5;
    const NOT_LEADER_FOR_PARTITION         = 6;
    const REQUEST_TIMED_OUT                = 7;
    const BROKER_NOT_AVAILABLE             = 8;
    const REPLICA_NOT_AVAILABLE            = 9;
    const MESSAGE_TOO_LARGE                = 10;
    const STALE_CONTROLLER_EPOCH           = 11;
    const OFFSET_METADATA_TOO_LARGE        = 12;
    const NETWORK_EXCEPTION                = 13;
    const GROUP_LOAD_IN_PROGRESS           = 14;
    const GROUP_COORDINATOR_NOT_AVAILABLE  = 15;
    const NOT_COORDINATOR_FOR_GROUP        = 16;
    const INVALID_TOPIC_EXCEPTION          = 17;
    const RECORD_LIST_TOO_LARGE            = 18;
    const NOT_ENOUGH_REPLICAS              = 19;
    const NOT_ENOUGH_REPLICAS_AFTER_APPEND = 20;
    const INVALID_REQUIRED_ACKS            = 21;
    const ILLEGAL_GENERATION               = 22;
    const INCONSISTENT_GROUP_PROTOCOL      = 23;
    const INVALID_GROUP_ID                 = 24;
    const UNKNOWN_MEMBER_ID                = 25;
    const INVALID_SESSION_TIMEOUT          = 26;
    const REBALANCE_IN_PROGRESS            = 27;
    const INVALID_COMMIT_OFFSET_SIZE       = 28;
    const TOPIC_AUTHORIZATION_FAILED       = 29;
    const GROUP_AUTHORIZATION_FAILED       = 30;
    const CLUSTER_AUTHORIZATION_FAILED     = 31;
    const INVALID_TIMESTAMP                = 32;
    const UNSUPPORTED_SASL_MECHANISM       = 33;
    const ILLEGAL_SASL_STATE               = 34;
    const UNSUPPORTED_VERSION              = 35;

    /**
     * Mapping from the codes to class names
     *
     * @var array
     */
    private static $codeToClassMap = [
        self::UNKNOWN                          => UnknownError::class,
        self::OFFSET_OUT_OF_RANGE              => OffsetOutOfRange::class,
        self::CORRUPT_MESSAGE                  => CorruptMessage::class,
        self::UNKNOWN_TOPIC_OR_PARTITION       => UnknownTopicOrPartition::class,
        self::INVALID_FETCH_SIZE               => InvalidFetchSize::class,
        self::LEADER_NOT_AVAILABLE             => LeaderNotAvailable::class,
        self::NOT_LEADER_FOR_PARTITION         => NotLeaderForPartition::class,
        self::REQUEST_TIMED_OUT                => RequestTimedOut::class,
        self::BROKER_NOT_AVAILABLE             => BrokerNotAvailable::class,
        self::REPLICA_NOT_AVAILABLE            => ReplicaNotAvailable::class,
        self::MESSAGE_TOO_LARGE                => MessageTooLarge::class,
        self::STALE_CONTROLLER_EPOCH           => StaleControllerEpoch::class,
        self::OFFSET_METADATA_TOO_LARGE        => OffsetMetadataTooLarge::class,
        self::NETWORK_EXCEPTION                => NetworkException::class,
        self::GROUP_LOAD_IN_PROGRESS           => GroupLoadInProgress::class,
        self::GROUP_COORDINATOR_NOT_AVAILABLE  => GroupCoordinatorNotAvailable::class,
        self::NOT_COORDINATOR_FOR_GROUP        => NotCoordinatorForGroup::class,
        self::INVALID_TOPIC_EXCEPTION          => InvalidTopicException::class,
        self::RECORD_LIST_TOO_LARGE            => RecordListTooLarge::class,
        self::NOT_ENOUGH_REPLICAS              => NotEnoughReplicas::class,
        self::NOT_ENOUGH_REPLICAS_AFTER_APPEND => NotEnoughReplicasAfterAppend::class,
        self::INVALID_REQUIRED_ACKS            => InvalidRequiredAcks::class,
        self::ILLEGAL_GENERATION               => IllegalGeneration::class,
        self::INCONSISTENT_GROUP_PROTOCOL      => InconsistentGroupProtocol::class,
        self::INVALID_GROUP_ID                 => InvalidGroupId::class,
        self::UNKNOWN_MEMBER_ID                => UnknownMemberId::class,
        self::INVALID_SESSION_TIMEOUT          => InvalidSessionTimeout::class,
        self::REBALANCE_IN_PROGRESS            => RebalanceInProgress::class,
        self::INVALID_COMMIT_OFFSET_SIZE       => InvalidCommitOffsetSize::class,
        self::TOPIC_AUTHORIZATION_FAILED       => TopicAuthorizationFailed::class,
        self::GROUP_AUTHORIZATION_FAILED       => GroupAuthorizationFailed::class,
        self::CLUSTER_AUTHORIZATION_FAILED     => ClusterAuthorizationFailed::class,
        self::INVALID_TIMESTAMP                => InvalidTimestamp::class,
        self::UNSUPPORTED_SASL_MECHANISM       => UnsupportedSaslMechanism::class,
        self::ILLEGAL_SASL_STATE               => IllegalSaslState::class,
        self::UNSUPPORTED_VERSION              => UnsupportedVersion::class
    ];

    /**
     * Additional context for the exception
     *
     * @var array
     */
    private $context = [];

    /**
     * Creates an instance of exception by error code
     *
     * @param integer         $errorCode Error code from the Kafka
     * @param array           $context   Additional context
     * @param Exception|null $previous
     *
     * @return KafkaException
     */
    final public static function fromCode($errorCode, array $context, Exception $previous = null)
    {
        if (!isset(self::$codeToClassMap[$errorCode])) {
            $exception = new UnknownError(['errorCode' => $errorCode] + $context, $previous);
        } else {
            $exceptionClass = self::$codeToClassMap[$errorCode];
            $exception      = new $exceptionClass($context, $previous);
        }

        return $exception;
    }

    /**
     * @inheritDoc
     */
    public function __construct(array $context = [], $code, Exception $previous = null)
    {
        $this->context = $context;
        $docBlock = (new \ReflectionObject($this))->getDocComment();
        $docBlock = preg_replace('/^\s*\/?\*+\/?/m', '', $docBlock);
        $docBlock = preg_replace('/\s{2,}/', '', $docBlock);

        $message = $docBlock . PHP_EOL . "Context: " . json_encode($context);
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the context for this exception
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
