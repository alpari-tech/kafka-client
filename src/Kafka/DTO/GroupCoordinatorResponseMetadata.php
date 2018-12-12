<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream;

/**
 * GroupCoordinator response data
 */
class GroupCoordinatorResponseMetadata implements BinarySchemeInterface
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

    public static function getScheme()
    {
        return [
            'nodeId' => Scheme::TYPE_INT32,
            'host'   => Scheme::TYPE_STRING,
            'port'   => Scheme::TYPE_INT32
        ];
    }
}
