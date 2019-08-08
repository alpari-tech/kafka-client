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

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        return [
            'groupId'      => Scheme::TYPE_STRING,
            'protocolType' => Scheme::TYPE_STRING
        ];
    }
}
