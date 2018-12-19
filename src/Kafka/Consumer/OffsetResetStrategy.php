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

namespace Alpari\Kafka\Consumer;

/**
 * Enumeration of offset reset strategies
 */
final class OffsetResetStrategy
{
    /**
     * Fetch the earliest available offset
     */
    public const EARLIEST = 'earliest';

    /**
     * Fetch the latest available offset for topic partition
     */
    public const LATEST = 'latest';

    /**
     * Do not fetch offset and throw an exception if offset is not available
     */
    public const NONE = 'none';
}
