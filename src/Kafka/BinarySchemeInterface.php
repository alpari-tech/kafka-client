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

namespace Alpari\Kafka;

interface BinarySchemeInterface
{
    /**
     * Returns definition of binary packet for the class or object
     */
    public static function getScheme(): array;
}
