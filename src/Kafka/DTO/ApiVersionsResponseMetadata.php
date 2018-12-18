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

    public static function getScheme(): array
    {
        return [
            'apiKey'     => Scheme::TYPE_INT16,
            'minVersion' => Scheme::TYPE_INT16,
            'maxVersion' => Scheme::TYPE_INT16,
        ];
    }
}
