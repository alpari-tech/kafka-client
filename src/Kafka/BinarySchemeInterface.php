<?php
/**
 * Copyright
 *
 * @author Alexander.Lisachenko
 * @date   29.06.2018
 */

namespace Protocol\Kafka;

interface BinarySchemeInterface
{
    /**
     * Returns definition of binary packet for the class or object
     */
    public static function getScheme(): array;
}
