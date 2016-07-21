<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\DTO\ApiVersionsResponseMetadata;
use Protocol\Kafka\Record;

/**
 * Api versions response
 */
class ApiVersionsResponse extends AbstractResponse
{

    /**
     * API versions supported by the broker.
     *
     * @var array|ApiVersionsResponseMetadata[]
     */
    public $apiVersions = [];

    /**
     * Error code.
     *
     * @var integer
     */
    public $errorCode;

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self Instance of current frame
     * @param string $data Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, $data)
    {
        list(
            $self->correlationId,
            $self->errorCode,
            $versionsNumber
        ) = array_values(unpack("NcorrelationId/nerrorCode/NapiVersionsNumber", $data));
        $data = substr($data, 10);

        for ($i=0; $i<$versionsNumber; $i++) {
            $apiVersionMetadata = new ApiVersionsResponseMetadata();
            list (
                $apiKey,
                $apiVersionMetadata->minVersion,
                $apiVersionMetadata->maxVersion
            ) = array_values(unpack("napiKey/nminVersion/nmaxVersion", $data));
            $data = substr($data, 6);
            
            $self->apiVersions[$apiKey] = $apiVersionMetadata;
        }
        
        return $self;
    }
}
