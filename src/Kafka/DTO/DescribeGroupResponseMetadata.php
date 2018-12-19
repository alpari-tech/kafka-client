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
 * DescribeGroup metadata DTO
 *
 * DescibeGroupMetadata => error_code group_id state protocol_type protocol [members]
 *   error_code => INT16
 *   group_id => STRING
 *   state => STRING
 *   protocol_type => STRING
 *   protocol => STRING
 *   members => member_id client_id client_host member_metadata member_assignment
 */
class DescribeGroupResponseMetadata implements BinarySchemeInterface
{

    /**
     * Error code for the group
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Name of the group
     *
     * @var string
     */
    public $groupId;

    /**
     * The current state of the group
     * (one of: Dead, Stable, AwaitingSync, or PreparingRebalance, or empty if there is no active group)
     *
     * @var string
     */
    public $state;

    /**
     * The current group protocol type (will be empty if there is no active group)
     *
     * @var string
     */
    public $protocolType;

    /**
     * The current group protocol (only provided if the group is Stable)
     *
     * @var string
     */
    public $protocol;

    /**
     * Current group members (only provided if the group is not Dead)
     *
     * @var DescribeGroupResponseMember[]
     */
    public $members = [];

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'errorCode'    => Scheme::TYPE_INT16,
            'groupId'      => Scheme::TYPE_STRING,
            'state'        => Scheme::TYPE_STRING,
            'protocolType' => Scheme::TYPE_STRING,
            'protocol'     => Scheme::TYPE_STRING,
            'members'      => ['memberId' => DescribeGroupResponseMember::class]
        ];
    }
}
