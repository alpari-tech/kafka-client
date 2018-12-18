<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Scheme;

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

    public function __construct($mechanism, $clientId = '', $correlationId = 0)
    {
        $this->mechanism = $mechanism;

        parent::__construct(Kafka::SASL_HANDSHAKE, $clientId, $correlationId);
    }

    public static function getScheme(): array
    {
        $header = parent::getScheme();

        return $header + [
            'mechanism' => Scheme::TYPE_STRING
        ];
    }
}
