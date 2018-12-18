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

namespace Protocol\Kafka\Enum;

final class SslProtocol
{
    const TLS = 'TLS';

    const TLSv1_1 = 'TLSv1_1';

    const TLSv1_2 = 'TLSv1_2';

    const SSL = 'SSL';

    const SSLv2 = 'SSLv2';

    const SSLv3 = 'SSLv3';
}
