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

namespace Protocol\Kafka\Error;

use Exception;

/**
 * The requested fetch size is invalid.
 */
class InvalidFetchSize extends KafkaException implements ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::INVALID_FETCH_SIZE, $previous);
    }
}
