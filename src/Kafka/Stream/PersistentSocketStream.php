<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka;
use Protocol\Kafka\Error\NetworkException;
use Protocol\Kafka\Stream;

/**
 * PersistentSocketStream allows to keep the connection to sockets between requests to increase performance
 */
class PersistentSocketStream extends SocketStream
{

    /**
     * {@inheritdoc}
     */
    protected function connect()
    {
        $streamSocket = @pfsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);
        if (!$streamSocket) {
            throw new NetworkException("Socket error {$errorNumber}: {$errorString}");
        }

        $this->streamSocket = $streamSocket;
    }

    /**
     * @inheritDoc
     */
    protected function disconnect()
    {
        // we don't want to close the connection, so don't call fclose() here
    }
}
