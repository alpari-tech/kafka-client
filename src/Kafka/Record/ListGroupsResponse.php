<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\DTO\ListGroupResponseProtocol;
use Protocol\Kafka\Scheme;

/**
 * List groups response
 *
 * ListGroups Response (Version: 0) => error_code [groups]
 *   error_code => INT16
 *   groups => group_id protocol_type
 *     group_id => STRING
 *     protocol_type => STRING
 */
class ListGroupsResponse extends AbstractResponse implements BinarySchemeInterface
{
    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * List of groups as keys and current protocols as values
     *
     * @var array
     */
    public $groups = [];

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'errorCode' => Scheme::TYPE_INT16,
            'groups'    => ['groupId' => ListGroupResponseProtocol::class]
        ];
    }
}
