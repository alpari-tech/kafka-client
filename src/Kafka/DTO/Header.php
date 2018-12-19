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
     */
    public $key;

    /**
     * Item arbitrary data
     */
    public $value;

    /**
     * Header constructor.
     *
     * @param string $key   Item key name
     * @param string $value Item arbitrary data
     */
    public function __construct(string $key = '', string $value = '')
    {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'key'   => Scheme::TYPE_VARCHAR_ZIGZAG,
            'value' => Scheme::TYPE_VARCHAR_ZIGZAG,
        ];
    }
}
