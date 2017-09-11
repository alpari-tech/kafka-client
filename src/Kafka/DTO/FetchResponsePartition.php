<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\DTO;

use Protocol\Kafka\Stream;

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

    /**
     * Unpacks the DTO from the binary buffer
     *
     * @param Stream $stream Binary buffer
     *
     * @return static
     */
    public static function unpack(Stream $stream)
    {
        $partition = new static();
        list(
            $partition->partition,
            $partition->errorCode,
            $partition->highwaterMarkOffset,
            $messageSetSize
        ) = array_values($stream->read('Npartition/nerrorCode/JhighwaterMarkOffset/NmessageSetSize'));

        for ($received = 0; $received < $messageSetSize; $received += ($messageSet->messageSize + 12)) {
            $messageSet = MessageSet::unpack($stream);
            $partition->messageSet[] = $messageSet;
        }

        return $partition;
    }
}
