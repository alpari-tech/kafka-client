<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\OffsetsResponsePartition;
use Protocol\Kafka\DTO\OffsetsResponseTopic;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * ListOffset response object
 *
 * ListOffsets Response (Version: 1) => [responses]
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition error_code timestamp offset
 *       partition => INT32
 *       error_code => INT16
 *       timestamp => INT64
 *       offset => INT64
 */
class OffsetsResponse extends AbstractResponse implements BinarySchemeInterface
{
    /**
     * List of broker metadata info
     *
     * @var OffsetsResponseTopic[]
     */
    public $topics = [];

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'topics' => ['topic' => OffsetsResponseTopic::class]
        ];
    }
}
