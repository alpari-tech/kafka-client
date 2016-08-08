<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2016
 */

namespace Protocol\Kafka\Record;

use Protocol\Kafka;
use Protocol\Kafka\Record;

/**
 * Basic class for all responses
 */
abstract class AbstractResponse extends Record
{
    /**
     * A user-supplied integer value that will be passed back with the response (INT32)
     *
     * @var integer
     */
    public $correlationId;
}
