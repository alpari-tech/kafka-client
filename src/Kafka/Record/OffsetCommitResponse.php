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


namespace Alpari\Kafka\Record;

use Alpari\Kafka\DTO\OffsetCommitResponseTopic;

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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'topics' => ['topic' => OffsetCommitResponseTopic::class]
        ];
    }
}
