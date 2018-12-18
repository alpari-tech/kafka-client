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

namespace Protocol;

class Kafka
{
    /**
     * The following are the numeric codes that the ApiKey in the request can take for each of the below request types.
     */
    public const PRODUCE             = 0;
    public const FETCH               = 1;
    public const OFFSETS             = 2;
    public const METADATA            = 3;
    public const LEADER_AND_ISR      = 4;
    public const STOP_REPLICA        = 5;
    public const UPDATE_METADATA     = 6;
    public const CONTROLLED_SHUTDOWN = 7;
    public const OFFSET_COMMIT       = 8;
    public const OFFSET_FETCH        = 9;
    public const GROUP_COORDINATOR   = 10;
    public const JOIN_GROUP          = 11;
    public const HEARTBEAT           = 12;
    public const LEAVE_GROUP         = 13;
    public const SYNC_GROUP          = 14;
    public const DESCRIBE_GROUPS     = 15;
    public const LIST_GROUPS         = 16;
    public const SASL_HANDSHAKE      = 17;
    public const API_VERSIONS        = 18;
}
