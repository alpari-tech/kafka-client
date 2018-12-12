<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\DescribeGroupResponseMetadata;

/**
 * Describe groups response
 */
class DescribeGroupsResponse extends AbstractResponse implements BinarySchemeInterface
{
    /**
     * List of groups as keys and group info as values
     *
     * @var array
     */
    public $groups = [];

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'groups' => ['groupId' => DescribeGroupResponseMetadata::class]
        ];
    }
}
