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

/**
 * No more brokers available in kafka cluster.
 */
class AllBrokersNotAvailable extends KafkaException implements ClientExceptionInterface
{

}
