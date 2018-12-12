<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * ApiVersions response data
 */
class ApiVersionsResponseMetadata implements BinarySchemeInterface
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

    public static function getScheme()
    {
        return [
            'apiKey'     => Scheme::TYPE_INT16,
            'minVersion' => Scheme::TYPE_INT16,
            'maxVersion' => Scheme::TYPE_INT16,
        ];
    }
}
