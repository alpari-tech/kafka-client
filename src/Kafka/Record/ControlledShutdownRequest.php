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


namespace Alpari\Kafka\Record;

use Alpari\Kafka;
use Alpari\Kafka\Scheme;

/**
 * This request asks for the controlled shutdown of specific broker
 *
 * ControlledShutdown Request (Version: 0) => broker_id
 *   broker_id => INT32
 */
class ControlledShutdownRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    protected const VERSION = 1;

    /**
     * Broker identifier to shutdown
     */
    private $brokerId;

    public function __construct(int $brokerId, string $clientId = '', int $correlationId = 0)
    {
        $this->brokerId = $brokerId;

        parent::__construct(Kafka::CONTROLLED_SHUTDOWN, $clientId, $correlationId);
    }

    /**
     * @inheritdoc
     */
    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'brokerId' => Scheme::TYPE_INT32
        ];
    }
}
