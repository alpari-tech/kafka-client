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


namespace Protocol\Kafka\Record;

use Protocol\Kafka\AbstractRecord;
use Protocol\Kafka\Scheme;

/**
 * Basic class for all requests
 *
 * Request Header => api_key api_version correlation_id client_id
 *   api_key => INT16
 *   api_version => INT16
 *   correlation_id => INT32
 *   client_id => NULLABLE_STRING
 */
abstract class AbstractRequest extends AbstractRecord
{
    /**
     * Version of API request, could be overridden in children classes
     *
     * @var int
     */
    const VERSION = 0;

    /**
     * The id of the request type. (INT16)
     *
     * @var integer
     */
    protected $apiKey;

    /**
     * The version of the API. (INT16)
     *
     * @var integer
     */
    protected $apiVersion;

    /**
     * A user-supplied integer value that will be passed back with the response (INT32)
     *
     * @var integer
     */
    protected $correlationId;

    /**
     * A user specified identifier for the client making the request.
     *
     * @var string
     */
    protected $clientId;

    /**
     * Global request counter, ideally this should be stored somewhere in the shared config to survive between requests
     *
     * @var int
     */
    private static $counter = 0;

    public function __construct($apiKey, $clientId = '', $correlationId = 0)
    {
        $this->apiKey        = $apiKey;
        $this->clientId      = $clientId;
        $this->correlationId = $correlationId ?: self::$counter++;
        $this->apiVersion    = static::VERSION;
        $this->messageSize   = Scheme::getObjectTypeSize($this) - 4 /* INT32 MessageSize */;
    }

    public static function getScheme(): array
    {
        return [
            'messageSize'   => Scheme::TYPE_INT32,
            'apiKey'        => Scheme::TYPE_INT16,
            'apiVersion'    => Scheme::TYPE_INT16,
            'correlationId' => Scheme::TYPE_INT32,
            'clientId'      => Scheme::TYPE_NULLABLE_STRING
        ];
    }
}
