<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\Record;

/**
 * Basic class for all requests
 */
abstract class AbstractRequest extends Record
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

        $this->setMessageData($this->packPayload());
    }

    /**
     * Implementation of packing the payload
     *
     * @return string
     */
    protected function packPayload()
    {
        $clientLength = strlen($this->clientId);

        return pack(
            "nnNna{$clientLength}",
            $this->apiKey,
            $this->apiVersion,
            $this->correlationId,
            $clientLength,
            $this->clientId
        );
    }
}
