<?php

namespace Protocol\Kafka\Error;

/**
 * No more brokers available in kafka cluster.
 */
class AllBrokersNotAvailable extends KafkaException implements ClientExceptionInterface
{

}
