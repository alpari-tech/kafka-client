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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

/**
 * Fetch request topic DTO
 *
 * FetchResponseAbortedTransaction => producer_id first_offset
 *   producer_id => INT64
 *   first_offset => INT64
 *
 * @since Version 4
 */
class FetchResponseAbortedTransaction implements BinarySchemeInterface
{
    /**
     * The producer id associated with the aborted transactions
     *
     * @var integer
     */
    public $producerId;

    /**
     * The first offset in the aborted transaction
     *
     * @var integer
     */
    public $firstOffset;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'producerId'  => Scheme::TYPE_INT64,
            'firstOffset' => Scheme::TYPE_INT64
        ];
    }
}
