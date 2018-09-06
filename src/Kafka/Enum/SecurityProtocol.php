<?php

namespace Protocol\Kafka\Enum;

/**
 * Possible values for security.protocol configuration parameter
 */
final class SecurityProtocol
{
    const PLAINTEXT = 'PLAINTEXT';

    const SSL = 'SSL';

    const SASL_PLAINTEXT = 'SASL_PLAINTEXT';

    const SASL_SSL = 'SASL_SSL';
}
