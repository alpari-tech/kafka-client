<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\BinarySchemeInterface;
use Protocol\Kafka\Scheme;

/**
 * ListGroupResponseGroup DTO
 *
 * ListGroupResponseGroup => group_id protocol_type
 *   group_id => STRING
 *   protocol_type => STRING
 */
class ListGroupResponseProtocol implements BinarySchemeInterface
{
    /**
     * The unique group identifier
     *
     * @var string
     */
    public $groupId;

    /**
     * Supported protocol type
     *
     * @var string
     */
    public $protocolType;

    public static function getScheme()
    {
        return [
            'groupId'      => Scheme::TYPE_STRING,
            'protocolType' => Scheme::TYPE_STRING
        ];
    }
}
