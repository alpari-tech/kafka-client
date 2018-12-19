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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'nodeId' => Scheme::TYPE_INT32,
            'host'   => Scheme::TYPE_STRING,
            'port'   => Scheme::TYPE_INT32
        ];
    }
}
