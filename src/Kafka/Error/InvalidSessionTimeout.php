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
 * The session timeout is not within the range allowed by the broker
 *
 * as configured by group.min.session.timeout.ms and group.max.session.timeout.ms
 */
class InvalidSessionTimeout extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_SESSION_TIMEOUT, $previous);
    }
}
