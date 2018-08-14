<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * Cluster authorization failed.
 */
class ClusterAuthorizationFailed extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::CLUSTER_AUTHORIZATION_FAILED, $previous);
    }
}
