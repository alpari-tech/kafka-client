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

namespace Alpari\Kafka;

use Alpari\Kafka\Common\Utils\ByteUtils;
use ReflectionClass;
use RuntimeException;
use function current;
use function is_array;
use function is_string;
use function key;
use function strlen;

/**
 * Scheme defines common types and API for reading and writing primitve types into
 */
class Scheme
{
    public const TYPE_INT8           =  1;
    public const TYPE_INT16          =  2;
    public const TYPE_INT32          =  3;
    public const TYPE_INT64          =  4;
    public const TYPE_VARINT         =  5;
    public const TYPE_VARLONG        =  6;
    public const TYPE_VARCHAR        =  7; // Varint-encoded length + string itself
    public const TYPE_STRING         =  8; // INT16-encoded length and then bytes of chars
    public const TYPE_BYTEARRAY      = 10; // INT32 size of data, then bytes of data
    public const TYPE_VARINT_ZIGZAG  = 11; // Varint + ZigZag encoding
    public const TYPE_VARLONG_ZIGZAG = 12; // Varlong + ZigZag encoding
    public const TYPE_VARCHAR_ZIGZAG = 13; // Varint-zigzag-encoded length + string itself
    public const FLAG_VARARRAY       = 14; // Array, which size is VARINT-encoded
    public const FLAG_NULLABLE       = 128; // Use -1 as null array/string

    /**
     *  INT16-encoded length and then bytes of chars, -1 as size means null value
     */
    public const TYPE_NULLABLE_STRING = self::TYPE_STRING | self::FLAG_NULLABLE;

    /**
     * Calculates the size of single item, can be scalar, array or object
     *
     * @param mixed $schemeType Scheme type
     * @param mixed $value Optional value to calculate size of string, arrays, object, etc...
     *
     * @return int
     */
    public static function getSingleTypeSize($schemeType, $value = null): int
    {
        // Let's check for the complex type mapping
        if (is_array($schemeType)) {
            return self::getArrayTypeSize($schemeType, $value);
        }

        // If it's string, then we have an object with internal scheme
        if (is_string($schemeType)) {
            return self::getObjectTypeSize($value);
        }

        switch ($schemeType) {
            case self::TYPE_INT8:
            case self::TYPE_INT16:
            case self::TYPE_INT32:
            case self::TYPE_INT64:
                return 2 ** ($schemeType - 1); // We assume sequence 1..4 and just use it as base for 2^type

            case self::TYPE_STRING:
            case self::TYPE_NULLABLE_STRING:
                $length = !empty($value) ? strlen($value) : 0;
                return 2 /* INT16 Size */ + $length;

            case self::TYPE_VARINT:
                return ByteUtils::sizeOfVarint($value);
            case self::TYPE_VARLONG:
                return ByteUtils::sizeOfVarlong($value);
            case self::TYPE_VARCHAR:
                $length = strlen($value);
                return ByteUtils::sizeOfVarint($length) + $length;

            case self::TYPE_VARINT_ZIGZAG:
                $encodedLength = ByteUtils::encodeZigZag($value);
                return ByteUtils::sizeOfVarint($encodedLength);

            case self::TYPE_VARLONG_ZIGZAG:
                $encodedLength = ByteUtils::encodeZigZag($value);
                return ByteUtils::sizeOfVarint($encodedLength);

            case self::TYPE_VARCHAR_ZIGZAG:
                $length        = !empty($value) ? strlen($value) : 0;
                $encodedLength = ByteUtils::encodeZigZag($length);
                return ByteUtils::sizeOfVarint($encodedLength) + $length;

            case self::TYPE_BYTEARRAY:
                return 4 /* INT32 Size */ + strlen($value);
        }

        throw new RuntimeException("Unknown scheme type {$schemeType}");
    }

    /**
     * Calculates the size of array in bytes
     *
     * @param array $schemeType Special notation for array
     * @param array|null $value Array of items or null for nullable arrays
     *
     * @return int
     */
    public static function getArrayTypeSize(array $schemeType, ?array $value = null): int
    {
        $isVarArray    = !empty($schemeType[self::FLAG_VARARRAY]);
        $isNullable    = !empty($schemeType[self::FLAG_NULLABLE]);
        $sizeType      = $isVarArray ? self::TYPE_VARINT : self::TYPE_INT32;
        $arrayItemType = current($schemeType);
        if ($value === null) {
            if (!$isNullable) {
                throw new \UnexpectedValueException('Received null value for not nullable array');
            }
            $itemCount = -1;
            $value     = []; // To continue with foreach loop
        } elseif (is_array($value)) {
            $itemCount = count($value);
        } else {
            $receivedType = gettype($value);
            throw new \UnexpectedValueException("Array type should receive only arrays, {$receivedType} received");
        }

        $size = self::getSingleTypeSize($sizeType, $itemCount);
        // TODO: add support for fixed-size arrays to prevent multiple calls in foreach
        foreach ($value as $singleItemValue) {
            $size += self::getSingleTypeSize($arrayItemType, $singleItemValue);
        }

        return $size;
    }

    /**
     * Calculates the size of object in bytes
     */
    public static function getObjectTypeSize(BinarySchemeInterface $object): int
    {
        $objectScheme = $object->getScheme();
        $sizeCalculator = function (array $objectScheme) use ($object) {
            $objectSize = 0;
            // TODO: add support for fixed-size objects and DTOs
            foreach ($objectScheme as $fieldKey => $schemeType) {
                $objectSize += Scheme::getSingleTypeSize($schemeType, $object->$fieldKey);
            }

            return $objectSize;
        };

        return $sizeCalculator->call($object, $objectScheme);
    }

    public static function readObjectFromStream(string $recordClass, Stream $stream, $path = '')
    {
        $scheme           = $recordClass::getScheme();
        $recordReflection = new ReflectionClass($recordClass);
        $record           = $recordReflection->newInstanceWithoutConstructor();

        $reader = function (array $scheme) use ($record, $stream, $path) {
            foreach ($scheme as $fieldKey => $schemeType) {
                $record->$fieldKey = Scheme::readSingleType($schemeType, $stream, "$path->{$fieldKey}");
            }
        };
        $reader->call($record, $scheme);

        return $record;
    }

    public static function writeObjectToStream(BinarySchemeInterface $record, Stream $stream): void
    {
        $scheme = $record->getScheme();
        $writer = function (array $scheme) use ($record, $stream) {
            foreach ($scheme as $fieldKey => $schemeType) {
                Scheme::writeSingleType($schemeType, $record->$fieldKey, $stream);
            }
        };
        $writer->call($record, $scheme);
    }

    public static function readSingleType($schemeType, Stream $stream, string $path = '')
    {
        // Let's check for the complex type mapping
        if (is_array($schemeType)) {
            $arrayItemType = current($schemeType);
            $arrayKeyName  = key($schemeType);
            $isVarArray    = !empty($schemeType[self::FLAG_VARARRAY]);
            $isNullable    = !empty($schemeType[self::FLAG_NULLABLE]);
            $sizeType      = $isVarArray ? self::TYPE_VARINT : self::TYPE_INT32;
            $arraySize     = self::readSingleType($sizeType, $stream, "{$path}[size]");
            // Special handling of null value type
            if ($arraySize === -1 && $isNullable) {
                return null;
            }

            $result = [];
            for ($index = 0; $index < $arraySize; $index++) {
                $value = self::readSingleType($arrayItemType, $stream, "{$path}[$index]");
                if (is_string($arrayKeyName)) {
                    $result[$value->$arrayKeyName] = $value;
                } else {
                    $result[] = $value;
                }
            }

            return $result;
        }

        // If it's string, then we have nested object that can be unpacked
        if (is_string($schemeType)) {
            return self::readObjectFromStream($schemeType, $stream, "{$path}:{$schemeType}");
        }

        switch ($schemeType) {
            case self::TYPE_INT8:
                return $stream->read('CINT8')['INT8'];

            case self::TYPE_INT16:
                $value = $stream->read('nINT16')['INT16'];
                if ($value & 0x8000) {
                    $value -= 0x10000;
                }
                return $value;

            case self::TYPE_INT32:
                $value = $stream->read('NINT32')['INT32'];
                if ($value & 0x80000000) {
                    $value -= 0x100000000;
                }
                return $value;

            case self::TYPE_INT64:
                return $stream->read('JINT64')['INT64'];

            case self::TYPE_VARINT:
                return $stream->readVarint();

            case self::TYPE_VARLONG:
                return $stream->readVarint(); // TODO: Need to use VARLONG type here

            case self::TYPE_VARCHAR:
                $dataLength = $stream->readVarint();
                $value      = null;
                if ($dataLength >= 0) {
                    $value = $stream->read("a{$dataLength}data")['data'];
                }
                return $value;

            case self::TYPE_VARINT_ZIGZAG:
                $encoded = $stream->readVarint();
                return ByteUtils::decodeZigZag($encoded);

            case self::TYPE_VARLONG_ZIGZAG:
                $encoded = $stream->readVarint(); // TODO: Need to use VARLONG type here
                return ByteUtils::decodeZigZag($encoded);

            case self::TYPE_VARCHAR_ZIGZAG:
                $encodedLength = $stream->readVarint();
                $dataLength    = ByteUtils::decodeZigZag($encodedLength);
                $value         = null;
                if ($dataLength >= 0) {
                    $value = $stream->read("a{$dataLength}data")['data'];
                }
                return $value;

            case self::TYPE_STRING:
                return $stream->readString();

            case self::TYPE_NULLABLE_STRING:
                $stringSize = self::readSingleType(self::TYPE_INT16, $stream, "{$path}[size]");
                // Special handling of null-values
                $value = null;
                if ($stringSize >= 0) {
                    $value = $stream->read("a{$stringSize}data")['data'];
                }
                return $value;

            case self::TYPE_BYTEARRAY:
                // TODO: Support nullable byte arrays
                return $stream->readByteArray();
        }

        throw new \RuntimeException('Unexpected scheme type received');
    }

    public static function writeSingleType($schemeType, $value, Stream $stream): void
    {
        // Let's check for the complex type mapping
        if (is_array($schemeType)) {
            $arrayItemType = current($schemeType);
            $isVarArray    = !empty($schemeType[self::FLAG_VARARRAY]);
            $isNullable    = !empty($schemeType[self::FLAG_NULLABLE]);
            $sizeType      = $isVarArray ? self::TYPE_VARINT : self::TYPE_INT32;
            // Special handling of null arrays
            if ($value === null) {
                if (!$isNullable) {
                    throw new \UnexpectedValueException('Received null value for not nullable array');
                }
                self::writeSingleType($sizeType, -1, $stream);
                return;
            }

            if (is_array($value)) {
                $itemCount = count($value);
                self::writeSingleType($sizeType, $itemCount, $stream);
            } else {
                $receivedType = gettype($value);
                throw new \UnexpectedValueException("Array type should receive only arrays, {$receivedType} received");
            }

            foreach ($value as $singleItemValue) {
                self::writeSingleType($arrayItemType, $singleItemValue, $stream);
            }
            return;
        }

        // If it's string, then we have nested object that can be packed into the stream
        if (is_string($schemeType)) {
            self::writeObjectToStream($value, $stream);
            return;
        }

        switch ($schemeType) {
            case self::TYPE_INT8:
                $stream->write('C', $value);
                return;
            case self::TYPE_INT16:
                $stream->write('n', $value);
                return;
            case self::TYPE_INT32:
                $stream->write('N', $value);
                return;
            case self::TYPE_INT64:
                $stream->write('J', $value);
                return;
            case self::TYPE_VARINT:
                $stream->writeVarint($value);
                return;
            case self::TYPE_VARLONG:
                $stream->writeVarint($value); // TODO: Need to use VARLONG type here
                return;
            case self::TYPE_VARCHAR:
                $dataLength = strlen($value);
                $stream->writeVarint($dataLength);
                $stream->writeBuffer($value);
                return;
            case self::TYPE_VARINT_ZIGZAG:
                $encoded = ByteUtils::encodeZigZag($value, 32);
                $stream->writeVarint($encoded);
                return;

            case self::TYPE_VARLONG_ZIGZAG:
                $encoded = ByteUtils::encodeZigZag($value, 64);
                $stream->writeVarint($encoded);
                return;

            case self::TYPE_VARCHAR_ZIGZAG:
                $dataLength    = !empty($value) ? strlen($value) : 0;
                $encodedLength = ByteUtils::encodeZigZag($dataLength);
                $stream->writeVarint($encodedLength);
                $stream->writeBuffer($value);
                return;

            case self::TYPE_STRING:
                $stream->writeString($value);
                return;
            case self::TYPE_NULLABLE_STRING:
                $size = $value === null ? -1 : strlen($value);
                $stream->write('n', $size);
                $stream->writeBuffer($value);
                return;
            case self::TYPE_BYTEARRAY:
                $stream->writeByteArray($value);
                return;
        }
    }
}
