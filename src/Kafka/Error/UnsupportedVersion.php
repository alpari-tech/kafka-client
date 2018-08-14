<?php

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The version of API is not supported.
 */
class UnsupportedVersion extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::UNSUPPORTED_VERSION, $previous);
    }
}
