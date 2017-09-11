<?php
/**
 * @author Alexander.Lisachenko
 * @date 28.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka\DTO\DescribeGroupMetadata;
use Protocol\Kafka\Record;
use Protocol\Kafka\Stream;

/**
 * Describe groups response
 */
class DescribeGroupsResponse extends AbstractResponse
{
    /**
     * List of groups as keys and group info as values
     *
     * @var array
     */
    public $groups = [];

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|static $self   Instance of current frame
     * @param Stream $stream Binary data
     *
     * @return Record
     *
     * DescribeGroupsResponse => [ErrorCode GroupId State ProtocolType Protocol Members]
     *   ErrorCode => int16
     *   GroupId => string
     *   State => string
     *   ProtocolType => string
     *   Protocol => string
     *   Members => [MemberId ClientId ClientHost MemberMetadata MemberAssignment]
     *     MemberId => string
     *     ClientId => string
     *     ClientHost => string
     *     MemberMetadata => bytes
     *     MemberAssignment => bytes

     */
    protected static function unpackPayload(Record $self, Stream $stream)
    {
        list(
            $self->correlationId,
            $groupNumber
        ) = array_values($stream->read('NcorrelationId/NgroupNumber'));

        for ($groupIndex = 0; $groupIndex < $groupNumber; $groupIndex++) {
            $groupMetadata = DescribeGroupMetadata::unpack($stream);
            $self->groups[$groupMetadata->groupId] = $groupMetadata;
        }

        return $self;
    }
}
