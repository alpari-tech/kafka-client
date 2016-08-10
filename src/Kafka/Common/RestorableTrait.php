<?php
/**
 * @author Alexander.Lisachenko
 * @date 09.08.2016
 */

namespace Protocol\Kafka\Common;

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
