<?php
/**
 * @author Alexander.Lisachenko
 * @date   29.07.2016
 */

namespace Protocol\Kafka\Consumer;

/**
 * Enumeration of offset reset strategies
 */
final class OffsetResetStrategy
{
    /**
     * Fetch the earliest available offset
     */
    const EARLIEST = 'earliest';

    /**
     * Fetch the latest available offset for topic partition
     */
    const LATEST = 'latest';

    /**
     * Do not fetch offset and throw an exception if offset is not available
     */
    const NONE = 'none';
}
