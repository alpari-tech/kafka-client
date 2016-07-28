<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka;
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
            return null;
        }

        return $this->read("a{$stringLength}string")['string'];
    }

    /**
     * Writes the string to the stream
     *
     * @param $string
     *
     * @return mixed
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
     *
     * @return mixed
     */
    public function writeByteArray($data)
    {
        $dataLength = strlen($data);
        $this->write("Na{$dataLength}", $dataLength, $data);
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
        $numMatches = preg_match_all('/(?:\/|^)(\w)(\d*)/', $format, $matches);
        if(empty($numMatches)) {
            throw new \InvalidArgumentException("Unknown format specified: {$format}");
        };
        $size = 0;
        for ($matchIndex = 0; $matchIndex < $numMatches; $matchIndex ++) {
            list ($modifier, $repitition) = [$matches[1][$matchIndex], $matches[2][$matchIndex]];
            if (!isset($tableSize[$modifier])) {
                throw new \InvalidArgumentException("Unknown modifier specified: $modifier");
            }
            $size += $tableSize[$modifier] * ($repitition !== '' ? $repitition : 1);
        }

        return $size;
    }
}
