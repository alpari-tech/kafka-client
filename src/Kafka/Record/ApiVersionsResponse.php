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

use Alpari\Kafka\DTO\ApiVersionsResponseMetadata;
use Alpari\Kafka\Scheme;

/**
 * Api versions response
 */
class ApiVersionsResponse extends AbstractResponse
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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return parent::getScheme() + [
            'errorCode'   => Scheme::TYPE_INT16,
            'apiVersions' => ['apiKey' => ApiVersionsResponseMetadata::class],
        ];
    }
}
