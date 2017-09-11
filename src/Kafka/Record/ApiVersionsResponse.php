<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\ApiVersionsResponseMetadata;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

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
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $self->errorCode,
            $versionsNumber
        ) = array_values($stream->read('NcorrelationId/nerrorCode/NapiVersionsNumber'));

        for ($i=0; $i<$versionsNumber; $i++) {
            $apiVersionMetadata = ApiVersionsResponseMetadata::unpack($stream);

            $self->apiVersions[$apiVersionMetadata->apiKey] = $apiVersionMetadata;
        }

        return $self;
    }
}
