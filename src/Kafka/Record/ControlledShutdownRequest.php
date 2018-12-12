<?php
/**
 * @author Alexander.Lisachenko
 * @date 27.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Scheme;

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
    const VERSION = 1;

    /**
     * Broker identifier to shutdown
     *
     * @var integer
     */
    private $brokerId;

    public function __construct($brokerId, $clientId = '', $correlationId = 0)
    {
        $this->brokerId = $brokerId;

        parent::__construct(Kafka::CONTROLLED_SHUTDOWN, $clientId, $correlationId);
    }

    public static function getScheme()
    {
        $header = parent::getScheme();

        return $header + [
            'brokerId' => Scheme::TYPE_INT32
        ];
    }
}
