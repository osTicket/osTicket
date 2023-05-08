<?php

namespace Laminas\Mail\Protocol;

use Laminas\Mail\Protocol\Pop3\Response;
use Laminas\Stdlib\ErrorHandler;

use function explode;
use function fclose;
use function fgets;
use function fwrite;
use function is_string;
use function md5;
use function rtrim;
use function stream_socket_enable_crypto;
use function strpos;
use function strtok;
use function strtolower;
use function substr;
use function trim;

class Pop3
{
    use ProtocolTrait;

    /**
     * Default timeout in seconds for initiating session
     */
    public const TIMEOUT_CONNECTION = 30;

    /**
     * saves if server supports top
     *
     * @var null|bool
     */
    public $hasTop;

    /** @var null|resource */
    protected $socket;

    /**
     * greeting timestamp for apop
     *
     * @var null|string
     */
    protected $timestamp;

    /**
     * Public constructor
     *
     * @param  string      $host           hostname or IP address of POP3 server, if given connect() is called
     * @param  int|null    $port           port of POP3 server, null for default (110 or 995 for ssl)
     * @param  bool|string $ssl            use ssl? 'SSL', 'TLS' or false
     * @param  bool        $novalidatecert set to true to skip SSL certificate validation
     */
    public function __construct($host = '', $port = null, $ssl = false, $novalidatecert = false)
    {
        $this->setNoValidateCert($novalidatecert);

        if ($host) {
            $this->connect($host, $port, $ssl);
        }
    }

    /**
     * Public destructor
     */
    public function __destruct()
    {
        $this->logout();
    }

    /**
     * Open connection to POP3 server
     *
     * @param  string      $host  hostname or IP address of POP3 server
     * @param  int|null    $port  of POP3 server, default is 110 (995 for ssl)
     * @param  string|bool $ssl   use 'SSL', 'TLS' or false
     * @throws Exception\RuntimeException
     * @return string welcome message
     */
    public function connect($host, $port = null, $ssl = false)
    {
        $transport = 'tcp';
        $isTls     = false;

        if ($ssl) {
            $ssl = strtolower($ssl);
        }

        switch ($ssl) {
            case 'ssl':
                $transport = 'ssl';
                if (! $port) {
                    $port = 995;
                }
                break;
            case 'tls':
                $isTls = true;
                // break intentionally omitted
            default:
                if (! $port) {
                    $port = 110;
                }
        }

        $this->socket = $this->setupSocket($transport, $host, $port, self::TIMEOUT_CONNECTION);

        $welcome = $this->readResponse();

        strtok($welcome, '<');
        $this->timestamp = strtok('>');
        if (! strpos($this->timestamp, '@')) {
            $this->timestamp = null;
        } else {
            $this->timestamp = '<' . $this->timestamp . '>';
        }

        if ($isTls) {
            $this->request('STLS');
            $result = stream_socket_enable_crypto($this->socket, true, $this->getCryptoMethod());
            if (! $result) {
                throw new Exception\RuntimeException('cannot enable TLS');
            }
        }

        return $welcome;
    }

    /**
     * Send a request
     *
     * @param string $request your request without newline
     * @throws Exception\RuntimeException
     */
    public function sendRequest($request)
    {
        ErrorHandler::start();
        $result = fwrite($this->socket, $request . "\r\n");
        $error  = ErrorHandler::stop();
        if (! $result) {
            throw new Exception\RuntimeException('send failed - connection closed?', 0, $error);
        }
    }

    /**
     * read a response
     *
     * @param  bool $multiline response has multiple lines and should be read until "<nl>.<nl>"
     * @throws Exception\RuntimeException
     * @return string response
     */
    public function readResponse($multiline = false)
    {
        $response = $this->readRemoteResponse();

        if ($response->status() != '+OK') {
            throw new Exception\RuntimeException('last request failed');
        }

        $message = $response->message();

        if ($multiline) {
            $message = '';
            $line    = fgets($this->socket);
            while ($line && rtrim($line, "\r\n") != '.') {
                if ($line[0] == '.') {
                    $line = substr($line, 1);
                }
                $message .= $line;
                $line     = fgets($this->socket);
            }
        }

        return $message;
    }

    /**
     * read a response
     * return extracted status / message from response

     * @throws Exception\RuntimeException
     */
    protected function readRemoteResponse(): Response
    {
        ErrorHandler::start();
        $result = fgets($this->socket);
        $error  = ErrorHandler::stop();
        if (! is_string($result)) {
            throw new Exception\RuntimeException('read failed - connection closed?', 0, $error);
        }

        $result = trim($result);
        if (strpos($result, ' ')) {
            [$status, $message] = explode(' ', $result, 2);
        } else {
            $status  = $result;
            $message = '';
        }

        return new Response($status, $message);
    }

    /**
     * Send request and get response
     *
     * @see sendRequest()
     * @see readResponse()
     *
     * @param  string $request    request
     * @param  bool   $multiline  multiline response?
     * @return string             result from readResponse()
     */
    public function request($request, $multiline = false)
    {
        $this->sendRequest($request);
        return $this->readResponse($multiline);
    }

    /**
     * End communication with POP3 server (also closes socket)
     */
    public function logout()
    {
        if ($this->socket) {
            try {
                $this->request('QUIT');
            } catch (Exception\ExceptionInterface) {
                // ignore error - we're closing the socket anyway
            }

            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Get capabilities from POP3 server
     *
     * @return array list of capabilities
     */
    public function capa()
    {
        $result = $this->request('CAPA', true);
        return explode("\n", $result);
    }

    /**
     * Login to POP3 server. Can use APOP
     *
     * @param  string $user     username
     * @param  string $password password
     * @param  bool   $tryApop  should APOP be tried?
     */
    public function login($user, $password, $tryApop = true)
    {
        if ($tryApop && $this->timestamp) {
            try {
                $this->request("APOP $user " . md5($this->timestamp . $password));
                return;
            } catch (Exception\ExceptionInterface) {
                // ignore
            }
        }

        $this->request("USER $user");
        $this->request("PASS $password");
    }

    /**
     * Make STAT call for message count and size sum
     *
     * @param  int $messages  out parameter with count of messages
     * @param  int $octets    out parameter with size in octets of messages
     */
    public function status(&$messages, &$octets)
    {
        $messages = 0;
        $octets   = 0;
        $result   = $this->request('STAT');

        [$messages, $octets] = explode(' ', $result);
    }

    /**
     * Make LIST call for size of message(s)
     *
     * @param  int|null $msgno number of message, null for all
     * @return int|array size of given message or list with array(num => size)
     */
    public function getList($msgno = null)
    {
        if ($msgno !== null) {
            $result = $this->request("LIST $msgno");

            [, $result] = explode(' ', $result);
            return (int) $result;
        }

        $result   = $this->request('LIST', true);
        $messages = [];
        $line     = strtok($result, "\n");
        while ($line) {
            [$no, $size]         = explode(' ', trim($line));
            $messages[(int) $no] = (int) $size;
            $line                = strtok("\n");
        }

        return $messages;
    }

    /**
     * Make UIDL call for getting a uniqueid
     *
     * @param  int|null $msgno number of message, null for all
     * @return string|array uniqueid of message or list with array(num => uniqueid)
     */
    public function uniqueid($msgno = null)
    {
        if ($msgno !== null) {
            $result = $this->request("UIDL $msgno");

            [, $result] = explode(' ', $result);
            return $result;
        }

        $result = $this->request('UIDL', true);

        $result   = explode("\n", $result);
        $messages = [];
        foreach ($result as $line) {
            if (! $line) {
                continue;
            }
            [$no, $id]           = explode(' ', trim($line), 2);
            $messages[(int) $no] = $id;
        }

        return $messages;
    }

    /**
     * Make TOP call for getting headers and maybe some body lines
     * This method also sets hasTop - before it it's not known if top is supported
     *
     * The fallback makes normal RETR call, which retrieves the whole message. Additional
     * lines are not removed.
     *
     * @param  int  $msgno    number of message
     * @param  int  $lines    number of wanted body lines (empty line is inserted after header lines)
     * @param  bool $fallback fallback with full retrieve if top is not supported
     * @throws Exception\RuntimeException
     * @throws Exception\ExceptionInterface
     * @return string message headers with wanted body lines
     */
    public function top($msgno, $lines = 0, $fallback = false)
    {
        if ($this->hasTop === false) {
            if ($fallback) {
                return $this->retrieve($msgno);
            }

            throw new Exception\RuntimeException('top not supported and no fallback wanted');
        }
        $this->hasTop = true;

        $lines = ! $lines || $lines < 1 ? 0 : (int) $lines;

        try {
            $result = $this->request("TOP $msgno $lines", true);
        } catch (Exception\ExceptionInterface $e) {
            $this->hasTop = false;
            if ($fallback) {
                $result = $this->retrieve($msgno);
            } else {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Make a RETR call for retrieving a full message with headers and body
     *
     * @param  int $msgno  message number
     * @return string message
     */
    public function retrieve($msgno)
    {
        return $this->request("RETR $msgno", true);
    }

    /**
     * Make a NOOP call, maybe needed for keeping the server happy
     */
    public function noop()
    {
        $this->request('NOOP');
    }

    /**
     * Make a DELE count to remove a message
     *
     * @param int $msgno
     */
    public function delete($msgno)
    {
        $this->request("DELE $msgno");
    }

    /**
     * Make RSET call, which rollbacks delete requests
     */
    public function undelete()
    {
        $this->request('RSET');
    }
}
