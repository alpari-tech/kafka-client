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


namespace Protocol\Kafka\Consumer;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;
use Protocol\Kafka\Stream;

/**
 * Subscription information that is used for the synchronization between consumers
 *
 * ProtocolMetadata => Version Subscription UserData
 *   Version => int16
 *   Subscription => [Topic]
 *     Topic => string
 *   UserData => bytes
 */
class Subscription implements BinarySchemeInterface
{

    /**
     * This is a version id.
     *
     * @var integer
     */
    public $version;

    /**
     * This property holds all the topics for the consumer.
     *
     * @var array
     */
    public $topics;

    /**
     * The UserData field can be used by custom partition assignment strategies.
     *
     * For example, in a sticky partitioning implementation, this field can contain the assignment from the previous
     * generation. In a resource-based assignment strategy, it could include the number of cpus on the machine hosting
     * each consumer instance.
     *
     * @var string
     */
    public $userData;

    /**
     * Subscription constructor.
     *
     * @param string[] $topics   List of topics
     * @param int      $version
     * @param string   $userData Additional user data
     */
    public function __construct(array $topics, $version = 0, $userData = '')
    {
        $this->topics   = $topics;
        $this->version  = $version;
        $this->userData = $userData;
    }

    /**
     * Returns definition of binary packet for the class or object
     *
     * @return array
     */
    public static function getScheme(): array
    {
        return [
            'version'  => Scheme::TYPE_INT16,
            'topics'   => [Scheme::TYPE_STRING],
            'userData' => Scheme::TYPE_BYTEARRAY
        ];
    }
}
