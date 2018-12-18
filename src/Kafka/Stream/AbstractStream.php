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


namespace Protocol\Kafka\Stream;

use Protocol\Kafka\Stream;

abstract class AbstractStream implements Stream
{

    /**
     * Reads a string from the stream
     *
     * @return string
     */
    public function readString()
    {
        $stringLength = $this->read('nlength')['length'];
        if ($stringLength === 0xFFFF) {
            throw new \UnexpectedValueException("Received -1 length for not nullable string");
        }

        return $this->read("a{$stringLength}string")['string'];
    }

    /**
     * Writes the string to the stream
     *
     * @param $string
     */
    public function writeString($string)
    {
        $stringLength = strlen($string);
        $this->write("na{$stringLength}", $stringLength, $string);
    }

    /**
     * Reads a byte array from the stream
     *
     * @return string
     */
    public function readByteArray()
    {
        $dataLength = $this->read('Nlength')['length'];
        if ($dataLength === 0xFFFFFFFF) {
            return null;
        }

        return $this->read("a{$dataLength}data")['data'];
    }

    /**
     * Writes the string to the stream
     *
     * @param string $data Binary data
     */
    public function writeByteArray($data)
    {
        $dataLength = strlen($data);
        $this->write("Na{$dataLength}", $dataLength, $data);
    }

    /**
     * Reads varint from the stream
     *
     * @return integer
     */
    public function readVarint()
    {
        $value  = 0;
        $offset = 0;
        do {
            $byte   = $this->read('Cbyte')['byte'];
            $value  += ($byte & 0x7f) << $offset;
            $offset += 7;
        } while (($byte & 0x80) !== 0);

        return $value;
    }

    /**
     * Writes a varint value from the stream
     *
     * @param integer $value
     */
    public function writeVarint($value)
    {
        do {
            $byte  = $value & 0x7f;
            $value >>= 7;
            $byte  = $value > 0 ? ($byte | 0x80) : $byte;
            $this->write('C', $byte);
        } while ($value > 0);
    }

    /**
     * Writes the raw buffer into the stream as-is
     *
     * @param string $buffer
     */
    public function writeBuffer($buffer)
    {
        $bufferLength = $buffer ? strlen($buffer) : 0;
        $this->write("a{$bufferLength}", $buffer);
    }

    /**
     * Calculates the format size for unpack() operation
     *
     * @param string $format
     *
     * @return int
     */
    protected static function packetSize($format)
    {
        static $tableSize = [
            'a' => 1,
            'c' => 1,
            'C' => 1,
            's' => 2,
            'S' => 2,
            'n' => 2,
            'v' => 2,
            'i' => PHP_INT_SIZE,
            'I' => PHP_INT_SIZE,
            'l' => 4,
            'L' => 4,
            'N' => 4,
            'V' => 4,
            'q' => 8,
            'Q' => 8,
            'J' => 8,
            'P' => 8,
        ];
        static $cache = [];
        if (isset($cache[$format])) {
            return $cache[$format];
        }

        $numMatches = preg_match_all('/(?:\/|^)(\w)(\d*)/', $format, $matches);
        if(empty($numMatches)) {
            throw new \InvalidArgumentException("Unknown format specified: {$format}");
        };
        $size = 0;
        for ($matchIndex = 0; $matchIndex < $numMatches; $matchIndex ++) {
            [$modifier, $repitition] = [$matches[1][$matchIndex], $matches[2][$matchIndex]];
            if (!isset($tableSize[$modifier])) {
                throw new \InvalidArgumentException("Unknown modifier specified: $modifier");
            }
            $size += $tableSize[$modifier] * ($repitition !== '' ? $repitition : 1);
        }

        $cache[$format] = $size;

        return $size;
    }
}
