<?php
/**
 * @author Alexander.Lisachenko
 * @date   12.09.2017
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * Header object contains key-value pair of associated metadata for record.
 *
 * Header => HeaderKey HeaderVal
 *   HeaderKeyLen => varint
 *   HeaderKey => string
 *   HeaderValueLen => varint
 *   HeaderValue => data
 *
 * @since 0.11.0
 * @see   https://cwiki.apache.org/confluence/display/KAFKA/KIP-82+-+Add+Record+Headers
 */
class Header implements BinarySchemeInterface
{
    /**
     * Item key name
     *
     * @var string
     */
    public $key;

    /**
     * Item arbitrary data
     *
     * @var string
     */
    public $value;

    public function __construct($key = '', $value = '')
    {
        $this->key   = $key;
        $this->value = $value;
    }

    public static function getScheme(): array
    {
        return [
            'key'   => Scheme::TYPE_VARCHAR_ZIGZAG,
            'value' => Scheme::TYPE_VARCHAR_ZIGZAG,
        ];
    }
}
