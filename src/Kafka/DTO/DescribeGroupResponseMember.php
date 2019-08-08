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


namespace Alpari\Kafka\DTO;

use Alpari\Kafka\BinarySchemeInterface;
use Alpari\Kafka\Scheme;

/**
 * DescribeGroupResponseMember metadata DTO
 *
 * DescribeGroupResponseMember => member_id client_id client_host member_metadata member_assignment
 *   member_id => STRING
 *   client_id => STRING
 *   client_host => STRING
 *   member_metadata => BYTES
 *   member_assignment => BYTES
 */
class DescribeGroupResponseMember implements BinarySchemeInterface
{
    /**
     * 	The memberId assigned by the coordinator
     *
     * @var string
     */
    public $memberId;

    /**
     * The client id used in the member's latest join group request
     *
     * @var string
     */
    public $clientId;

    /**
     * The client host used in the request session corresponding to the member's join group.
     *
     * @var string
     */
    public $clientHost;

    /**
     * The metadata corresponding to the current group protocol in use (will only be present if the group is stable).
     *
     * @var string Binary data
     */
    public $memberMetadata;

    /**
     * The current assignment provided by the group leader (will only be present if the group is stable).
     *
     * @var string
     */
    public $memberAssignment;

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'memberId'         => Scheme::TYPE_STRING,
            'clientId'         => Scheme::TYPE_STRING,
            'clientHost'       => Scheme::TYPE_STRING,
            'memberMetadata'   => Scheme::TYPE_BYTEARRAY,
            'memberAssignment' => Scheme::TYPE_BYTEARRAY,
        ];
    }
}
