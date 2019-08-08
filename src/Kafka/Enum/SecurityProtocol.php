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

namespace Alpari\Kafka\Enum;

/**
 * Possible values for security.protocol configuration parameter
 */
final class SecurityProtocol
{
    public const PLAINTEXT = 'PLAINTEXT';

    public const SSL = 'SSL';

    public const SASL_PLAINTEXT = 'SASL_PLAINTEXT';

    public const SASL_SSL = 'SASL_SSL';
}
