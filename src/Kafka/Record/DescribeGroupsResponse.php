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

use Alpari\Kafka\DTO\DescribeGroupResponseMetadata;

/**
 * Describe groups response
 */
class DescribeGroupsResponse extends AbstractResponse
{
    /**
     * List of groups as keys and group info as values
     */
    public $groups = [];

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'groups' => ['groupId' => DescribeGroupResponseMetadata::class]
        ];
    }
}
