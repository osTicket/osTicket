<?php

namespace Laminas\Mail\Protocol;

use Generator;
use Laminas\Mail\Headers;

use function array_key_exists;
use function array_replace_recursive;
use function chunk_split;
use function fclose;
use function fgets;
use function fopen;
use function fwrite;
use function implode;
use function ini_get;
use function is_array;
use function rewind;
use function rtrim;
use function stream_socket_enable_crypto;
use function strlen;
use function strtolower;
use function substr;

/**
 * SMTP implementation of Laminas\Mail\Protocol\AbstractProtocol
 *
 * Minimum implementation according to RFC2821: EHLO, MAIL FROM, RCPT TO, DATA,
 * RSET, NOOP, QUIT
 */
class Smtp extends AbstractProtocol
{
    use ProtocolTrait;

    /**
     * RFC 5322 section-2.2.3 specifies maximum of 998 bytes per line.
     * This may not be exceeded.
     *
     * @see https://tools.ietf.org/html/rfc5322#section-2.2.3
     */
    public const SMTP_LINE_LIMIT = 998;

    /**
     * The transport method for the socket
     *
     * @var string
     */
    protected $transport = 'tcp';

    /**
     * Indicates that a session is requested to be secure
     *
     * @var string
     */
    protected $secure;

    /**
     * Indicates an smtp session has been started by the HELO command
     *
     * @var bool
     */
    protected $sess = false;

    /**
     * Indicates an smtp AUTH has been issued and authenticated
     *
     * @var bool
     */
    protected $auth = false;

    /**
     * Indicates a MAIL command has been issued
     *
     * @var bool
     */
    protected $mail = false;

    /**
     * Indicates one or more RCTP commands have been issued
     *
     * @var bool
     */
    protected $rcpt = false;

    /**
     * Indicates that DATA has been issued and sent
     *
     * @var bool
     */
    protected $data;

    /**
     * Whether or not send QUIT command
     *
     * @var bool
     */
    protected $useCompleteQuit = true;

    /**
     * The first argument may be an array of all options. If so, it must include
     * the 'host' and 'port' keys in order to ensure that all required values
     * are present.
     *
     * @param  string|array $host
     * @param  null|int $port
     * @param  null|array   $config
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($host = '127.0.0.1', $port = null, ?array $config = null)
    {
        // Did we receive a configuration array?
        if (is_array($host)) {
            // Merge config array with principal array, if provided
            if (is_array($config)) {
                $config = array_replace_recursive($host, $config);
            } else {
                $config = $host;
            }

            // Look for a host key; if none found, use default value
            if (isset($config['host'])) {
                $host = $config['host'];
            } else {
                $host = '127.0.0.1';
            }

            // Look for a port key; if none found, use default value
            if (isset($config['port'])) {
                $port = $config['port'];
            } else {
                $port = null;
            }
        }

        // If we don't have a config array, initialize it
        if (null === $config) {
            $config = [];
        }

        if (isset($config['ssl'])) {
            switch (strtolower($config['ssl'])) {
                case 'tls':
                    $this->secure = 'tls';
                    break;

                case 'ssl':
                    $this->transport = 'ssl';
                    $this->secure    = 'ssl';
                    if ($port === null) {
                        $port = 465;
                    }
                    break;

                case '':
                    // fall-through
                case 'none':
                    break;

                default:
                    throw new Exception\InvalidArgumentException($config['ssl'] . ' is unsupported SSL type');
            }
        }

        if (array_key_exists('use_complete_quit', $config)) {
            $this->setUseCompleteQuit($config['use_complete_quit']);
        }

        // If no port has been specified then check the master PHP ini file. Defaults to 25 if the ini setting is null.
        if ($port === null) {
            if (($port = ini_get('smtp_port')) == '') {
                $port = 25;
            }
        }

        if (array_key_exists('novalidatecert', $config)) {
            $this->setNoValidateCert($config['novalidatecert']);
        }

        parent::__construct($host, $port);
    }

    /**
     * Set whether or not send QUIT command
     *
     * @param bool $useCompleteQuit use complete quit
     * @return bool
     */
    public function setUseCompleteQuit($useCompleteQuit)
    {
        return $this->useCompleteQuit = (bool) $useCompleteQuit;
    }

    /**
     * Read $data as lines terminated by "\n"
     *
     * @return Generator|string[]
     */
    private static function chunkedReader(string $data, int $chunkSize = 4096): Generator
    {
        if (($fp = fopen("php://temp", "r+")) === false) {
            throw new Exception\RuntimeException('cannot fopen');
        }
        if (fwrite($fp, $data) === false) {
            throw new Exception\RuntimeException('cannot fwrite');
        }
        rewind($fp);

        $line = null;
        while (($buffer = fgets($fp, $chunkSize)) !== false) {
            $line .= $buffer;

            // This is optimization to avoid calling length() in a loop.
            // We need to match a condition that is when:
            // 1. maximum was read from fgets, which is $chunkSize-1
            // 2. last byte of the buffer is not \n
            //
            // to access last byte of buffer, we can do
            // - $buffer[strlen($buffer)-1]
            // and when maximum is read from fgets, then:
            // - strlen($buffer) === $chunkSize-1
            // - strlen($buffer)-1 === $chunkSize-2
            // which means this is also true:
            // - $buffer[strlen($buffer)-1] === $buffer[$chunkSize-2]
            //
            // the null coalesce works, as string offset can never be null
            $lastByte = $buffer[$chunkSize - 2] ?? null;

            // partial read, continue loop to read again to complete the line
            // compare \n first as that's usually false
            if ($lastByte !== "\n" && $lastByte !== null) {
                continue;
            }

            yield $line;
            $line = null;
        }

        if ($line !== null) {
            yield $line;
        }

        fclose($fp);
    }

    /**
     * Whether or not send QUIT command
     *
     * @return bool
     */
    public function useCompleteQuit()
    {
        return $this->useCompleteQuit;
    }

    /**
     * Connect to the server with the parameters given in the constructor.
     *
     * @return bool
     */
    public function connect()
    {
        $this->socket = $this->setupSocket(
            $this->transport,
            $this->host,
            $this->port,
            self::TIMEOUT_CONNECTION
        );
        return true;
    }

    /**
     * Initiate HELO/EHLO sequence and set flag to indicate valid smtp session
     *
     * @param  string $host The client hostname or IP address (default: 127.0.0.1)
     * @throws Exception\RuntimeException
     */
    public function helo($host = '127.0.0.1')
    {
        // Respect RFC 2821 and disallow HELO attempts if session is already initiated.
        if ($this->sess === true) {
            throw new Exception\RuntimeException('Cannot issue HELO to existing session');
        }

        // Validate client hostname
        if (! $this->validHost->isValid($host)) {
            throw new Exception\RuntimeException(implode(', ', $this->validHost->getMessages()));
        }

        // Initiate helo sequence
        $this->_expect(220, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        $this->ehlo($host);

        // If a TLS session is required, commence negotiation
        if ($this->secure == 'tls') {
            $this->_send('STARTTLS');
            $this->_expect(220, 180);
            if (! stream_socket_enable_crypto($this->socket, true, $this->getCryptoMethod())) {
                throw new Exception\RuntimeException('Unable to connect via TLS');
            }
            $this->ehlo($host);
        }

        $this->startSession();
        $this->auth();
    }

    /**
     * Returns the perceived session status
     *
     * @return bool
     */
    public function hasSession()
    {
        return $this->sess;
    }

    /**
     * Send EHLO or HELO depending on capabilities of smtp host
     *
     * @param  string $host The client hostname or IP address (default: 127.0.0.1)
     * @throws Exception\ExceptionInterface
     */
    protected function ehlo($host)
    {
        // Support for older, less-compliant remote servers. Tries multiple attempts of EHLO or HELO.
        try {
            $this->_send('EHLO ' . $host);
            $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        } catch (Exception\ExceptionInterface) {
            $this->_send('HELO ' . $host);
            $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        }
    }

    /**
     * Issues MAIL command
     *
     * @param  string $from Sender mailbox
     * @throws Exception\RuntimeException
     */
    public function mail($from)
    {
        if ($this->sess !== true) {
            throw new Exception\RuntimeException('A valid session has not been started');
        }

        $this->_send('MAIL FROM:<' . $from . '>');
        $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2

        // Set mail to true, clear recipients and any existing data flags as per 4.1.1.2 of RFC 2821
        $this->mail = true;
        $this->rcpt = false;
        $this->data = false;
    }

    /**
     * Issues RCPT command
     *
     * @param  string $to Receiver(s) mailbox
     * @throws Exception\RuntimeException
     */
    public function rcpt($to)
    {
        if ($this->mail !== true) {
            throw new Exception\RuntimeException('No sender reverse path has been supplied');
        }

        // Set rcpt to true, as per 4.1.1.3 of RFC 2821
        $this->_send('RCPT TO:<' . $to . '>');
        $this->_expect([250, 251], 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
        $this->rcpt = true;
    }

    /**
     * Issues DATA command
     *
     * @param  string $data
     * @throws Exception\RuntimeException
     */
    public function data($data)
    {
        // Ensure recipients have been set
        if ($this->rcpt !== true) { // Per RFC 2821 3.3 (page 18)
            throw new Exception\RuntimeException('No recipient forward path has been supplied');
        }

        $this->_send('DATA');
        $this->_expect(354, 120); // Timeout set for 2 minutes as per RFC 2821 4.5.3.2

        $reader = self::chunkedReader($data);
        foreach ($reader as $line) {
            $line = rtrim($line, "\r\n");
            if (isset($line[0]) && $line[0] === '.') {
                // Escape lines prefixed with a '.'
                $line = '.' . $line;
            }

            if (strlen($line) > self::SMTP_LINE_LIMIT) {
                // Long lines are "folded" by inserting "<CR><LF><SPACE>"
                // https://tools.ietf.org/html/rfc5322#section-2.2.3
                // Add "-1" to stay within limits,
                // because Headers::FOLDING includes a byte for space character after \r\n
                $chunks = chunk_split($line, self::SMTP_LINE_LIMIT - 1, Headers::FOLDING);
                $line   = substr($chunks, 0, -strlen(Headers::FOLDING));
            }

            $this->_send($line);
        }

        $this->_send('.');
        $this->_expect(250, 600); // Timeout set for 10 minutes as per RFC 2821 4.5.3.2
        $this->data = true;
    }

    /**
     * Issues the RSET command end validates answer
     *
     * Can be used to restore a clean smtp communication state when a
     * transaction has been cancelled or commencing a new transaction.
     */
    public function rset()
    {
        $this->_send('RSET');
        // MS ESMTP doesn't follow RFC, see https://zendframework.com/issues/browse/ZF-1377
        $this->_expect([250, 220]);

        $this->mail = false;
        $this->rcpt = false;
        $this->data = false;
    }

    /**
     * Issues the NOOP command end validates answer
     *
     * Not used by Laminas\Mail, could be used to keep a connection alive or check if it is still open.
     */
    public function noop()
    {
        $this->_send('NOOP');
        $this->_expect(250, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
    }

    /**
     * Issues the VRFY command end validates answer
     *
     * Not used by Laminas\Mail.
     *
     * @param  string $user User Name or eMail to verify
     */
    public function vrfy($user)
    {
        $this->_send('VRFY ' . $user);
        $this->_expect([250, 251, 252], 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
    }

    /**
     * Issues the QUIT command and clears the current session
     */
    public function quit()
    {
        if ($this->sess) {
            $this->auth = false;

            if ($this->useCompleteQuit()) {
                $this->_send('QUIT');
                $this->_expect(221, 300); // Timeout set for 5 minutes as per RFC 2821 4.5.3.2
            }

            $this->stopSession();
        }
    }

    /**
     * Default authentication method
     *
     * This default method is implemented by AUTH adapters to properly authenticate to a remote host.
     *
     * @throws Exception\RuntimeException
     */
    public function auth()
    {
        if ($this->auth === true) {
            throw new Exception\RuntimeException('Already authenticated for this session');
        }
    }

    /**
     * Closes connection
     */
    public function disconnect()
    {
        $this->_disconnect();
    }

    /**
     * Disconnect from remote host and free resource
     */
    // @codingStandardsIgnoreLine PSR2.Methods.MethodDeclaration.Underscore
    protected function _disconnect()
    {
        // Make sure the session gets closed
        $this->quit();
        parent::_disconnect();
    }

    /**
     * Start mail session
     */
    protected function startSession()
    {
        $this->sess = true;
    }

    /**
     * Stop mail session
     */
    protected function stopSession()
    {
        $this->sess = false;
    }
}
