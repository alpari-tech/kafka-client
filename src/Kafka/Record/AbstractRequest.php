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


namespace Alpari\Kafka\Record;

use Alpari\Kafka\AbstractRecord;
use Alpari\Kafka\Scheme;

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
     */
    protected const VERSION = 0;

    /**
     * The id of the request type. (INT16)
     */
    protected $apiKey;

    /**
     * The version of the API. (INT16)
     */
    protected $apiVersion;

    /**
     * A user specified identifier for the client making the request.
     */
    protected $clientId;

    /**
     * Global request counter, ideally this should be stored somewhere in the shared config to survive between requests
     */
    private static $counter = 0;

    public function __construct(int $apiKey, string $clientId = '', int $correlationId = 0)
    {
        $this->apiKey        = $apiKey;
        $this->clientId      = $clientId;
        $this->correlationId = $correlationId ?: self::$counter++;
        $this->apiVersion    = static::VERSION;
        $this->messageSize   = Scheme::getObjectTypeSize($this) - 4 /* INT32 MessageSize */;
    }

    /**
     * @inheritdoc
     */
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
