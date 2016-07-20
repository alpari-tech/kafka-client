<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka;

/**
 * Fetch response DTO
 */
class FetchResponsePartition
{
    /**
     * The id of the partition this response is for.
     *
     * @var integer
     */
    public $partition;

    /**
     * The error from this partition, if any.
     *
     * Errors are given on a per-partition basis because a given partition may be unavailable or maintained on a
     * different host, while others may have successfully accepted the produce request.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * The offset at the end of the log for this partition. This can be used by the client to determine how many
     * messages behind the end of the log they are.
     *
     * @var integer
     */
    public $highwaterMarkOffset;

    /**
     * @var array|MessageSet[]
     */
    public $messageSet = [];
}
