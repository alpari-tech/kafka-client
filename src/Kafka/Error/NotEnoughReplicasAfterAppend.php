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

/**
 * Messages are written to the log, but to fewer in-sync replicas than required.
 */
class NotEnoughReplicasAfterAppend extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::NOT_ENOUGH_REPLICAS_AFTER_APPEND, $previous);
    }
}
