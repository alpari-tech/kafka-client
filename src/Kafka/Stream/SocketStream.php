<?php
/**
 * @author Alexander.Lisachenko
 * @date   26.07.2016
 */

namespace Protocol\Kafka\Stream;

use Protocol\Kafka\Common\Config;
use Protocol\Kafka\Enum\SecurityProtocol;
use Protocol\Kafka\Enum\SslProtocol;
use Protocol\Kafka\Error\InvalidConfiguration;
use Protocol\Kafka\Error\NetworkException;

/**
 * Implementation of simple socket stream
 */
class SocketStream extends AbstractStream
{
    /**
     * Internal socket
     *
     * @var resource
     */
    protected $streamSocket;

    /**
     * Host name
     *
     * @var string
     */
    protected $host;

    /**
     * Port number
     *
     * @var integer
     */
    protected $port;

    /**
     * Timeout for connection
     *
     * @var integer
     */
    protected $timeout;

    /**
     * Broker configuration
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Flag that determines if connection was established
     *
     * @var boolean
     */
    protected $isConnected;

    /**
     * Socket stream constructor
     *
     * @param string  $tcpAddress        Tcp address for connection
     * @param array   $configuration     Configuration options
     * @param integer $connectionTimeout Timeout for connection
     */
    public function __construct($tcpAddress, array $configuration, $connectionTimeout = null)
    {
        $tcpInfo = parse_url($tcpAddress);
        if ($tcpInfo === false || !isset($tcpInfo['host'])) {
            throw new InvalidConfiguration("Malformed tcp address: {$tcpAddress}");
        }
        $this->host          = $tcpInfo['host'];
        $this->port          = $tcpInfo['port'] ?? 9092;
        $this->timeout       = $connectionTimeout ?? ini_get("default_socket_timeout");
        $this->configuration = $configuration;
    }

    /**
     * Writes arguments to the stream
     *
     * @param string $format       Format for packing arguments
     * @param array  ...$arguments List of arguments for packing
     *
     * @see pack() manual for format
     *
     * @return void
     */
    public function write($format, ...$arguments)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $packedData = pack($format, ...$arguments);

        $packedDataLength = strlen($packedData);
        for ($written = 0; $written < $packedDataLength; $written += $result) {
            $result = @fwrite($this->streamSocket, substr($packedData, $written));
            if ($result === false || feof($this->streamSocket)) {
                if (!$this->isConnected()) {
                    $this->connect();
                    $result = 0; // try to write once again the same data
                } else {
                    throw new NetworkException(['error' => 'Can not write to the stream']);
                }
            }
        }
    }

    /**
     * Reads information from the stream, advanced internal pointer
     *
     * @param string $format Format for unpacking arguments
     * @see unpack() manual for format
     *
     * @return array List of unpacked arguments
     */
    public function read($format)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $packetSize   = self::packetSize($format);
        $streamBuffer = '';

        for ($received = 0; $received < $packetSize; $received += strlen($result)) {
            $result = fread($this->streamSocket, $packetSize - $received);
            if ($result === false || feof($this->streamSocket)) {
                if (!$this->isConnected()) {
                    $this->connect();
                    $result = '';
                } else {
                    throw new NetworkException(['error' => 'Can not read from the stream']);
                }
            }
            $streamBuffer .= $result;
        }

        $arguments = unpack($format, $streamBuffer);

        return $arguments;
    }

    /**
     * Automatic resource clean up
     */
    final public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Performs connection to the specified socket address
     */
    protected function connect()
    {
        $socketFlags = STREAM_CLIENT_CONNECT;
        if (!empty($this->configuration[Config::STREAM_ASYNC_CONNECT])) {
            $socketFlags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if (!empty($this->configuration[Config::STREAM_PERSISTENT_CONNECTION])) {
            $socketFlags |= STREAM_CLIENT_PERSISTENT;
        }

        $streamContext = $this->createStreamContext();
        $streamSocket  = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errorNumber,
            $errorString,
            $this->timeout,
            $socketFlags,
            $streamContext
        );

        if (!$streamSocket) {
            throw new NetworkException(compact('errorNumber', 'errorString'));
        }
        stream_set_write_buffer($streamSocket, $this->configuration[Config::SEND_BUFFER_BYTES]);
        stream_set_read_buffer($streamSocket, $this->configuration[Config::RECEIVE_BUFFER_BYTES]);
        if ($this->configuration[Config::SECURITY_PROTOCOL] === SecurityProtocol::SSL) {
            $this->encryptChannel($streamSocket);
        }

        $this->streamSocket = $streamSocket;
        $this->isConnected  = true;
    }

    /**
     * Performs the disconnect operation
     */
    protected function disconnect()
    {
        if (is_resource($this->streamSocket) && empty($this->configuration[Config::STREAM_PERSISTENT_CONNECTION])) {
            fclose($this->streamSocket);
        }
        $this->isConnected = false;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return is_resource($this->streamSocket) &&
            stream_socket_get_name($this->streamSocket, true);
    }

    /**
     * Creates context for underlying socket from configuration
     *
     * @return resource
     */
    private function createStreamContext()
    {
        $contextOptions = [];

        if (!empty($this->configuration[Config::SSL_CA_CERT_LOCATION])) {
            $contextOptions['ssl']['cafile'] = $this->ensureValidFile(
                $this->configuration[Config::SSL_CA_CERT_LOCATION],
                "CA file {file} is not accessible."
            );
        }

        if (!empty($this->configuration[Config::SSL_CLIENT_CERT_LOCATION])) {
            $contextOptions['ssl']['local_cert'] = $this->ensureValidFile(
                $this->configuration[Config::SSL_CLIENT_CERT_LOCATION],
                "Client certificate file {file} is not accessible."
            );
        }

        if (!empty($this->configuration[Config::SSL_KEY_LOCATION])) {
            $contextOptions['ssl']['local_pk'] = $this->ensureValidFile(
                $this->configuration[Config::SSL_KEY_LOCATION],
                "Key file {file} is not accessible."
            );
        }

        if (!empty($this->configuration[Config::SSL_KEY_PASSWORD])) {
            $contextOptions['ssl']['passphrase'] = $this->configuration[Config::SSL_KEY_PASSWORD];
        }

        return stream_context_create($contextOptions);
    }

    /**
     * Validates given file name and return it as a result
     *
     * @param string $fileName Absolute file name to validate
     * @param string $errorMessage Message to show if file is not accessible
     *
     * @return string Given file name
     */
    private function ensureValidFile($fileName, $errorMessage)
    {
        if (!is_readable($fileName)) {
            throw new InvalidConfiguration(
                strtr(
                    $errorMessage,
                    [
                        '{file}' => $fileName
                    ]
                )
            );
        }

        return $fileName;
    }

    /**
     * Encrypts channel between client and server
     *
     * @param resource $streamSocket Underlying socket
     *
     * @return void
     */
    private function encryptChannel($streamSocket)
    {
        static $cipherMap = [
            SslProtocol::TLS     => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            SslProtocol::TLSv1_1 => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
            SslProtocol::TLSv1_2 => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            SslProtocol::SSL     => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            SslProtocol::SSLv2   => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
            SslProtocol::SSLv3   => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
        ];

        $sslProtocol = $this->configuration[Config::SSL_PROTOCOL];
        if (!isset($cipherMap[$sslProtocol])) {
            throw new InvalidConfiguration(
                "SSL protocol {$sslProtocol} is not implemented."
            );
        }

        $errorMessage = null;
        set_error_handler(function ($code, $message) use (&$errorMessage) {
            $errorMessage = trim(str_replace('stream_socket_enable_crypto():', '', $message));
        });

        try {
            $isCryptoEnabled = stream_socket_enable_crypto(
                $streamSocket,
                true,
                $cipherMap[$sslProtocol]
            );
        } finally {
            restore_error_handler();
        }

        if ($isCryptoEnabled === false) {
            throw new NetworkException(
                [
                    'error' => "Failed to initialize encryption via {$sslProtocol} protocol: {$errorMessage}.",
                ]
            );
        }
    }

    /**
     * Checks if stream is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return feof($this->streamSocket);
    }
}
