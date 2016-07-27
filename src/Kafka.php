<?php
/**
 * @author Alexander.Lisachenko
 */

namespace Protocol;

class Kafka
{
    /**
     * Protocol version 0 implementation
     */
    const VERSION_0 = 0;

    /**
     * Protocol version implementation
     */
    const VERSION = 1;

    /**
     * Number of bytes in a kafka header
     */
    const HEADER_LEN = 4;

    /**
     * Format of kafka header for unpacking in PHP
     *
     * RequestOrResponse => Size (RequestMessage | ResponseMessage)
     * Size => int32
     */
    const HEADER_FORMAT = 'Nsize';

    /**
     * Format of kafka request header for unpacking in PHP
     *
     * Request Header => api_key api_version correlation_id client_id
     *   api_key        => INT16
     *   api_version    => INT16
     *   correlation_id => INT32
     *   client_id      => NULLABLE_STRING
     */
    const REQUEST_HEADER_FORMAT = 'napiKey/napiVersion/NcorrelationId/ZclientId';

    /**
     * The following are the numeric codes that the ApiKey in the request can take for each of the below request types.
     */
    const PRODUCE             = 0;
    const FETCH               = 1;
    const OFFSETS             = 2;
    const METADATA            = 3;
    const LEADER_AND_ISR      = 4;
    const STOP_REPLICA        = 5;
    const UPDATE_METADATA     = 6;
    const CONTROLLED_SHUTDOWN = 7;
    const OFFSET_COMMIT       = 8;
    const OFFSET_FETCH        = 9;
    const GROUP_COORDINATOR   = 10;
    const JOIN_GROUP          = 11;
    const HEARTBEAT           = 12;
    const LEAVE_GROUP         = 13;
    const SYNC_GROUP          = 14;
    const DESCRIBE_GROUPS     = 15;
    const LIST_GROUPS         = 16;
    const SASL_HANDSHAKE      = 17;
    const API_VERSIONS        = 18;
}
