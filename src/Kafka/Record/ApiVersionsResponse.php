<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\ApiVersionsResponseMetadata;
use Protocol\Kafka\Scheme;

/**
 * Api versions response
 */
class ApiVersionsResponse extends AbstractResponse implements BinarySchemeInterface
{

    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * API versions supported by the broker.
     *
     * @var ApiVersionsResponseMetadata[]
     */
    public $apiVersions = [];

    public static function getScheme()
    {
        return parent::getScheme() + [
            'errorCode'   => Scheme::TYPE_INT16,
            'apiVersions' => ['apiKey' => ApiVersionsResponseMetadata::class],
        ];
    }
}
