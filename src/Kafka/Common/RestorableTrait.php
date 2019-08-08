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


namespace Alpari\Kafka\Common;

/**
 * Simple implementation for classes that can be restored after var_export
 */
trait RestorableTrait
{
    /**
     * @inheritDoc
     */
    public static function __set_state(array $cachedData)
    {
        $self = new static;
        foreach ($cachedData as $key=>$value) {
            $self->$key = $value;
        }

        return $self;
    }
}
