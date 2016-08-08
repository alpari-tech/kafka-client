<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;
use Protocol\Kafka\Stream;

/**
 * ApiVersions response data
 */
class ApiVersionsResponseMetadata
{
    /**
     * Numerical code of API
     *
     * @var integer
     */
    public $apiKey;

    /**
     * Minimum supported version.
     *
     * @var integer
     */
    public $minVersion;

    /**
     * Maximum supported version.
     *
     * @var integer
     */
    public $maxVersion;

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $apiVersionMetadata = new static();
        list (
            $apiVersionMetadata->apiKey,
            $apiVersionMetadata->minVersion,
            $apiVersionMetadata->maxVersion
        ) = array_values($stream->read('napiKey/nminVersion/nmaxVersion'));

        return $apiVersionMetadata;
    }
}
