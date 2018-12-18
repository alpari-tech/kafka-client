<?php
/**
 * @author Alexander.Lisachenko
 * @date 15.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\OffsetCommitResponseTopic;

/**
 * Offset commit response object
 *
 * OffsetCommit Response (Version: 2) => [responses]
 *   responses => topic [partition_responses]
 *     topic => STRING
 *     partition_responses => partition error_code
 *       partition => INT32
 *       error_code => INT16
 */
class OffsetCommitResponse extends AbstractResponse
{
    /**
     * List of topics with partition result
     *
     * @var OffsetCommitResponseTopic[]
     */
    public $topics = [];

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'topics' => ['topic' => OffsetCommitResponseTopic::class]
        ];
    }
}
