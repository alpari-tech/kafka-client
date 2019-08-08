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
 * Possible values for ssl.protocol configuration parameter
 */
final class SslProtocol
{
    public const TLS = 'TLS';

    public const TLSv1_1 = 'TLSv1_1';

    public const TLSv1_2 = 'TLSv1_2';

    public const SSL = 'SSL';

    public const SSLv2 = 'SSLv2';

    public const SSLv3 = 'SSLv3';
}
