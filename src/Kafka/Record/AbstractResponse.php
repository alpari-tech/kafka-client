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
 * Basic class for all responses
 */
abstract class AbstractResponse extends AbstractRecord
{
    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'messageSize'   => Scheme::TYPE_INT32,
            'correlationId' => Scheme::TYPE_INT32,
        ];
    }
}
