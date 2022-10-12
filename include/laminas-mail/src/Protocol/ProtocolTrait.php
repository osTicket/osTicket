<?php

namespace Laminas\Mail\Protocol;

use Laminas\Stdlib\ErrorHandler;

use function defined;
use function sprintf;
use function stream_context_create;
use function stream_set_timeout;
use function stream_socket_client;

use const STREAM_CLIENT_CONNECT;
use const STREAM_CRYPTO_METHOD_TLS_CLIENT;
use const STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
use const STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

/**
 * https://bugs.php.net/bug.php?id=69195
 */
trait ProtocolTrait
{
    /**
     * If set to true, do not validate the SSL certificate
     *
     * @var null|bool
     */
    protected $novalidatecert;

    public function getCryptoMethod(): int
    {
        // Allow the best TLS version(s) we can
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        // PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
        // so add them back in manually if we can
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        return $cryptoMethod;
    }

    /**
     * Do not validate SSL certificate
     *
     * @todo Update to return self when minimum supported PHP version is 7.4+
     * @param  bool $novalidatecert Set to true to disable certificate validation
     * @return $this
     */
    public function setNoValidateCert(bool $novalidatecert)
    {
        $this->novalidatecert = $novalidatecert;
        return $this;
    }

    /**
     * Should we validate SSL certificate?
     */
    public function validateCert(): bool
    {
        return ! $this->novalidatecert;
    }

    /**
     * Prepare socket options
     *
     * @return array
     */
    private function prepareSocketOptions(): array
    {
        return $this->novalidatecert
            ? [
                'ssl' => [
                    'verify_peer_name' => false,
                    'verify_peer'      => false,
                ],
            ]
            : [];
    }

    /**
     * Setup connection socket
     *
     * @param  string   $host hostname or IP address of IMAP server
     * @param  int|null $port of IMAP server, default is 143 (993 for ssl)
     * @param  int      $timeout timeout in seconds for initiating session
     * @return resource The socket created.
     * @throws Exception\RuntimeException If unable to connect to host.
     */
    protected function setupSocket(
        string $transport,
        string $host,
        ?int $port,
        int $timeout
    ) {
        ErrorHandler::start();
        $socket = stream_socket_client(
            sprintf('%s://%s:%d', $transport, $host, $port),
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->prepareSocketOptions())
        );
        $error  = ErrorHandler::stop();

        if (! $socket) {
            throw new Exception\RuntimeException(sprintf(
                'cannot connect to host%s',
                $error ? sprintf('; error = %s (errno = %d )', $error->getMessage(), $error->getCode()) : ''
            ), 0, $error);
        }

        if (false === stream_set_timeout($socket, $timeout)) {
            throw new Exception\RuntimeException('Could not set stream timeout');
        }

        return $socket;
    }
}
