<?php
/**
 * @author Alexander.Lisachenko
 * @date   14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

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

    public static function getScheme()
    {
        return [
            'producerId'  => Scheme::TYPE_INT64,
            'firstOffset' => Scheme::TYPE_INT64
        ];
    }
}
