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

namespace Alpari\Kafka\Error;

use Exception;
use ReflectionObject;
use RuntimeException;

/**
 * Kafka uses numeric codes to indicate what problem occurred on the server.
 *
 * These can be translated by the client into exceptions or whatever the appropriate error handling mechanism in the
 * client language.
 */
abstract class KafkaException extends RuntimeException
{
    public const UNKNOWN = -1;

    public const NO_ERROR = 0;

    public const OFFSET_OUT_OF_RANGE              = 1;
    public const CORRUPT_MESSAGE                  = 2;
    public const UNKNOWN_TOPIC_OR_PARTITION       = 3;
    public const INVALID_FETCH_SIZE               = 4;
    public const LEADER_NOT_AVAILABLE             = 5;
    public const NOT_LEADER_FOR_PARTITION         = 6;
    public const REQUEST_TIMED_OUT                = 7;
    public const BROKER_NOT_AVAILABLE             = 8;
    public const REPLICA_NOT_AVAILABLE            = 9;
    public const MESSAGE_TOO_LARGE                = 10;
    public const STALE_CONTROLLER_EPOCH           = 11;
    public const OFFSET_METADATA_TOO_LARGE        = 12;
    public const NETWORK_EXCEPTION                = 13;
    public const GROUP_LOAD_IN_PROGRESS           = 14;
    public const GROUP_COORDINATOR_NOT_AVAILABLE  = 15;
    public const NOT_COORDINATOR_FOR_GROUP        = 16;
    public const INVALID_TOPIC_EXCEPTION          = 17;
    public const RECORD_LIST_TOO_LARGE            = 18;
    public const NOT_ENOUGH_REPLICAS              = 19;
    public const NOT_ENOUGH_REPLICAS_AFTER_APPEND = 20;
    public const INVALID_REQUIRED_ACKS            = 21;
    public const ILLEGAL_GENERATION               = 22;
    public const INCONSISTENT_GROUP_PROTOCOL      = 23;
    public const INVALID_GROUP_ID                 = 24;
    public const UNKNOWN_MEMBER_ID                = 25;
    public const INVALID_SESSION_TIMEOUT          = 26;
    public const REBALANCE_IN_PROGRESS            = 27;
    public const INVALID_COMMIT_OFFSET_SIZE       = 28;
    public const TOPIC_AUTHORIZATION_FAILED       = 29;
    public const GROUP_AUTHORIZATION_FAILED       = 30;
    public const CLUSTER_AUTHORIZATION_FAILED     = 31;
    public const INVALID_TIMESTAMP                = 32;
    public const UNSUPPORTED_SASL_MECHANISM       = 33;
    public const ILLEGAL_SASL_STATE               = 34;
    public const UNSUPPORTED_VERSION              = 35;

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
    private $context;

    /**
     * Creates an instance of exception by error code
     *
     * @param integer         $errorCode Error code from the Kafka
     * @param array           $context   Additional context
     * @param Exception|null $previous
     *
     * @return KafkaException
     */
    final public static function fromCode(int $errorCode, array $context, Exception $previous = null): KafkaException
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
    public function __construct(array $context, int $code, Exception $previous = null)
    {
        $this->context = $context;
        $docBlock = (new ReflectionObject($this))->getDocComment();
        $docBlock = preg_replace('/^\s*\/?\*+\/?/m', '', $docBlock);
        $docBlock = preg_replace('/\s{2,}/', '', $docBlock);

        $message = $docBlock . PHP_EOL . 'Context: ' . json_encode($context);
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the context for this exception
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
