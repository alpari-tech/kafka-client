<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;

/**
 * Broker metadata DTO
 */
class BrokerMetadata
{
    /**
     * The broker id.
     *
     * @var integer
     */
    public $nodeId;

    /**
     * The hostname of the broker.
     *
     * @var string
     */
    public $host;

    /**
     * The port on which the broker accepts requests.
     *
     * @var integer
     */
    public $port;
}
