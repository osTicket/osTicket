<?php

namespace Laminas\Mail\Storage;

use Laminas\Mail\Exception as MailException;
use Laminas\Mail\Protocol;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Message;
use Laminas\Mime;

use function array_combine;
use function array_key_exists;
use function is_string;
use function range;
use function strtolower;

class Pop3 extends AbstractStorage
{
    /**
     * protocol handler
     *
     * @var null|\Laminas\Mail\Protocol\Pop3
     */
    protected $protocol;

    /**
     * Count messages all messages in current box
     *
     * @return int number of messages
     * @throws ExceptionInterface
     * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
     */
    public function countMessages()
    {
        $count  = 0; // "Declare" variable before first usage.
        $octets = 0; // "Declare" variable since it's passed by reference
        $this->protocol->status($count, $octets);
        return (int) $count;
    }

    /**
     * get a list of messages with number and size
     *
     * @param int $id number of message
     * @return int|array size of given message of list with all messages as array(num => size)
     * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
     */
    public function getSize($id = 0)
    {
        $id = $id ?: null;
        return $this->protocol->getList($id);
    }

    /**
     * Fetch a message
     *
     * @param int $id number of message
     * @return Message
     * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
     */
    public function getMessage($id)
    {
        $bodyLines = 0;
        $message   = $this->protocol->top($id, $bodyLines, true);

        return new $this->messageClass([
            'handler'    => $this,
            'id'         => $id,
            'headers'    => $message,
            'noToplines' => $bodyLines < 1,
        ]);
    }

    /**
     * Get raw header of message or part
     *
     * @param  int               $id       number of message
     * @param  null|array|string $part     path to part or null for message header
     * @param  int               $topLines include this many lines with header (after an empty line)
     * @return string raw header
     * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
     * @throws ExceptionInterface
     */
    public function getRawHeader($id, $part = null, $topLines = 0)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Exception\RuntimeException('not implemented');
        }

        return $this->protocol->top($id, 0, true);
    }

    /**
     * Get raw content of message or part
     *
     * @param  int               $id   number of message
     * @param  null|array|string $part path to part or null for message content
     * @return string raw content
     * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
     * @throws ExceptionInterface
     */
    public function getRawContent($id, $part = null)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Exception\RuntimeException('not implemented');
        }

        $content = $this->protocol->retrieve($id);
        // TODO: find a way to avoid decoding the headers
        $headers = null; // "Declare" variable since it's passed by reference
        $body    = null; // "Declare" variable before first usage.
        Mime\Decode::splitMessage($content, $headers, $body);
        return $body;
    }

    /**
     * create instance with parameters
     * Supported parameters are
     *   - host hostname or ip address of POP3 server
     *   - user username
     *   - password password for user 'username' [optional, default = '']
     *   - port port for POP3 server [optional, default = 110]
     *   - ssl 'SSL' or 'TLS' for secure sockets
     *
     * @param  array|object|Protocol\Pop3 $params mail reader specific
     *     parameters or configured Pop3 protocol object
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($params)
    {
        $this->has['fetchPart'] = false;
        $this->has['top']       = null;
        $this->has['uniqueid']  = null;

        if ($params instanceof Protocol\Pop3) {
            $this->protocol = $params;
            return;
        }

        $params = ParamsNormalizer::normalizeParams($params);

        if (! isset($params['user'])) {
            throw new InvalidArgumentException('need at least user in params');
        }

        $host     = $params['host'] ?? 'localhost';
        $password = $params['password'] ?? '';
        $port     = $params['port'] ?? null;
        $ssl      = $params['ssl'] ?? false;

        if (null !== $port) {
            $port = (int) $port;
        }

        if (! is_string($ssl)) {
            $ssl = (bool) $ssl;
        }

        $this->protocol = new Protocol\Pop3();

        if (array_key_exists('novalidatecert', $params)) {
            $this->protocol->setNoValidateCert((bool) $params['novalidatecert']);
        }

        $this->protocol->connect((string) $host, $port, $ssl);
        $this->protocol->login((string) $params['user'], (string) $password);
    }

    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     */
    public function close()
    {
        $this->protocol->logout();
    }

    /**
     * Keep the server busy.
     *
     * @throws RuntimeException
     */
    public function noop()
    {
        $this->protocol->noop();
    }

    /**
     * Remove a message from server. If you're doing that from a web environment
     * you should be careful and use a uniqueid as parameter if possible to
     * identify the message.
     *
     * @param  int $id number of message
     * @throws RuntimeException
     */
    public function removeMessage($id)
    {
        $this->protocol->delete($id);
    }

    /**
     * get unique id for one or all messages
     *
     * if storage does not support unique ids it's the same as the message number
     *
     * @param int|null $id message number
     * @return array|string message number for given message or all messages as array
     * @throws ExceptionInterface
     */
    public function getUniqueId($id = null)
    {
        if (! $this->hasUniqueid) {
            if ($id) {
                return $id;
            }
            $count = $this->countMessages();
            if ($count < 1) {
                return [];
            }
            $range = range(1, $count);
            return array_combine($range, $range);
        }

        return $this->protocol->uniqueid($id);
    }

    /**
     * get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @throws InvalidArgumentException
     * @return int message number
     */
    public function getNumberByUniqueId($id)
    {
        if (! $this->hasUniqueid) {
            return $id;
        }

        $ids = $this->getUniqueId();
        foreach ($ids as $k => $v) {
            if ($v == $id) {
                return $k;
            }
        }

        throw new InvalidArgumentException('unique id not found');
    }

    /**
     * Special handling for hasTop and hasUniqueid. The headers of the first message is
     * retrieved if Top wasn't needed/tried yet.
     *
     * @see AbstractStorage::__get()
     *
     * @param  string $var
     * @return null|string
     */
    public function __get($var)
    {
        $result = parent::__get($var);
        if ($result !== null) {
            return $result;
        }

        if (strtolower($var) == 'hastop') {
            if ($this->protocol->hasTop === null) {
                // need to make a real call, because not all server are honest in their capas
                try {
                    $this->protocol->top(1, 0, false);
                } catch (MailException\ExceptionInterface) {
                    // ignoring error
                }
            }
            $this->has['top'] = $this->protocol->hasTop;
            return $this->protocol->hasTop;
        }

        if (strtolower($var) == 'hasuniqueid') {
            $id = null;
            try {
                $id = $this->protocol->uniqueid(1);
            } catch (MailException\ExceptionInterface) {
                // ignoring error
            }
            $this->has['uniqueid'] = (bool) $id;
            return $this->has['uniqueid'];
        }

        return $result;
    }
}
