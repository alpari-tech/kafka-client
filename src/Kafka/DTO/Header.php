<?php
/**
 * @author Alexander.Lisachenko
 * @date 12.09.2017
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

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
 * @see https://cwiki.apache.org/confluence/display/KAFKA/KIP-82+-+Add+Record+Headers
 */
class Header
{
    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return array Key-value pair of header
     */
    public static function unpack(Stream $stream)
    {
        $headerKeyLength   = $stream->readVarint();
        $headerKey         = $stream->read("a{$headerKeyLength}key")['key'];
        $headerValueLength = $stream->readVarint();
        $headerValue       = $stream->read("a{$headerValueLength}value")['value'];

        return [$headerKey, $headerValue];
    }

    /**
     * Packs key-value header pair into the stream
     *
     * @param Stream $stream Instance of stream
     * @param string $key    Key name
     * @param string $value  Value
     */
    public static function pack(Stream $stream, $key, $value)
    {
        $headerKeyLength   = strlen($key);
        $headerValueLength = strlen($value);
        $stream->writeVarint($headerKeyLength);
        $stream->write("a{$headerKeyLength}", $key);
        $stream->writeVarint($headerValueLength);
        $stream->write("a{$headerValueLength}", $value);
    }
}
