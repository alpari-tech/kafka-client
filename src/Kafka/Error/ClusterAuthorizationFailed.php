<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Cluster authorization failed.
 */
class ClusterAuthorizationFailed extends \RuntimeException implements KafkaException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, self::CLUSTER_AUTHORIZATION_FAILED, $previous);
    }
}
