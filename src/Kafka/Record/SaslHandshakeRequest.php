<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * This request queries the supported SASL mechanisms on the broker
 */
class SaslHandshakeRequest extends AbstractRequest
{
    /**
     * SASL Mechanism chosen by the client.
     *
     * @var string
     */
    private $mechanism;

    public function __construct($mechanism, $correlationId = 0, $clientId = '')
    {
        $this->mechanism = $mechanism;

        parent::__construct(Kafka::SASL_HANDSHAKE, $correlationId, $clientId);
    }

    /**
     * @inheritDoc
     */
    protected function packPayload()
    {
        $payload         = parent::packPayload();
        $mechanismLength = strlen($this->mechanism);

        $payload .= pack("na{$mechanismLength}", $mechanismLength, $this->mechanism);

        return $payload;
    }
}