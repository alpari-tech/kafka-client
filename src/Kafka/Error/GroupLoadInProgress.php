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
 * The coordinator is loading and hence can't process requests for this group.
 */
class GroupLoadInProgress extends KafkaException implements RetriableException, ServerExceptionInterface
{
    public function __construct(array $context, Exception $previous = null)
    {
        parent::__construct($context, self::GROUP_LOAD_IN_PROGRESS, $previous);
    }
}
