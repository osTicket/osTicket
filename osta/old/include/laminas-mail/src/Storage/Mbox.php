<?php

namespace Laminas\Mail\Storage;

use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Message\File;
use Laminas\Mail\Storage\Message\MessageInterface;
use Laminas\Stdlib\ErrorHandler;

use function array_combine;
use function count;
use function fclose;
use function fgets;
use function filemtime;
use function fopen;
use function fseek;
use function ftell;
use function is_dir;
use function is_resource;
use function is_subclass_of;
use function range;
use function str_starts_with;
use function stream_get_contents;
use function strlen;
use function strtolower;
use function trim;

use const E_WARNING;

class Mbox extends AbstractStorage
{
    /**
     * file handle to mbox file
     *
     * @var null|resource
     */
    protected $fh;

    /**
     * filename of mbox file for __wakeup
     *
     * @var string
     */
    protected $filename;

    /**
     * modification date of mbox file for __wakeup
     *
     * @var int
     */
    protected $filemtime;

    /**
     * start and end position of messages as array('start' => start, 'separator' => headersep, 'end' => end)
     *
     * @var array
     */
    protected $positions;

    /**
     * used message class, change it in an extended class to extend the returned message class
     *
     * @var class-string<MessageInterface>
     */
    protected $messageClass = File::class;

    /**
     * end of Line for messages
     *
     * @var string|null
     */
    // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty
    protected $messageEOL;

    /**
     * Count messages all messages in current box
     *
     * @return int number of messages
     * @throws ExceptionInterface
     */
    public function countMessages()
    {
        return count($this->positions);
    }

    /**
     * Get a list of messages with number and size
     *
     * @param  int|null $id  number of message or null for all messages
     * @return int|array size of given message of list with all messages as array(num => size)
     */
    public function getSize($id = 0)
    {
        if ($id) {
            $pos = $this->positions[$id - 1];
            return $pos['end'] - $pos['start'];
        }

        $result = [];
        foreach ($this->positions as $num => $pos) {
            $result[$num + 1] = $pos['end'] - $pos['start'];
        }

        return $result;
    }

    /**
     * Get positions for mail message or throw exception if id is invalid
     *
     * @param int $id number of message
     * @throws Exception\InvalidArgumentException
     * @return array positions as in positions
     */
    protected function getPos($id)
    {
        if (! isset($this->positions[$id - 1])) {
            throw new Exception\InvalidArgumentException('id does not exist');
        }

        return $this->positions[$id - 1];
    }

    /**
     * Fetch a message
     *
     * @param  int $id number of message
     * @return File
     * @throws ExceptionInterface
     */
    public function getMessage($id)
    {
        // TODO that's ugly, would be better to let the message class decide
        if (
            is_subclass_of($this->messageClass, File::class)
            || strtolower($this->messageClass) === strtolower(File::class)
        ) {
            // TODO top/body lines
            $messagePos = $this->getPos($id);

            $messageClassParams = [
                'file'     => $this->fh,
                'startPos' => $messagePos['start'],
                'endPos'   => $messagePos['end'],
            ];

            if (isset($this->messageEOL)) {
                $messageClassParams['EOL'] = $this->messageEOL;
            }

            return new $this->messageClass($messageClassParams);
        }

        /** @todo Uncomment once we know how to count body lines */
        // $bodyLines = 0;

        $message = $this->getRawHeader($id);

        /* Once we know how to count body lines, we should uncomment the
         * following, which would append the body content to the headers.
         *
        if ($bodyLines) {
            $message .= "\n";
            while ($bodyLines-- && ftell($this->fh) < $this->positions[$id - 1]['end']) {
                $message .= fgets($this->fh);
            }
        }
         */

        return new $this->messageClass(['handler' => $this, 'id' => $id, 'headers' => $message]);
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
        $messagePos = $this->getPos($id);
        // TODO: toplines
        return stream_get_contents($this->fh, $messagePos['separator'] - $messagePos['start'], $messagePos['start']);
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
        $messagePos = $this->getPos($id);
        return stream_get_contents($this->fh, $messagePos['end'] - $messagePos['separator'], $messagePos['separator']);
    }

    /**
     * Create instance with parameters
     * Supported parameters are:
     *   - filename filename of mbox file
     *
     * @param  array|object|Config $params mail reader specific parameters
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($params)
    {
        $params = ParamsNormalizer::normalizeParams($params);

        if (! isset($params['filename'])) {
            throw new Exception\InvalidArgumentException('no valid filename given in params');
        }

        if (isset($params['messageEOL'])) {
            $this->messageEOL = (string) $params['messageEOL'];
        }

        $this->openMboxFile((string) $params['filename']);
        $this->has['top']      = true;
        $this->has['uniqueid'] = false;
    }

    /**
     * check if given file is a mbox file
     *
     * if $file is a resource its file pointer is moved after the first line
     *
     * @param  resource|string $file stream resource of name of file
     * @param  bool $fileIsString file is string or resource
     * @return bool file is mbox file
     */
    protected function isMboxFile($file, $fileIsString = true)
    {
        if ($fileIsString) {
            ErrorHandler::start(E_WARNING);
            $file = fopen($file, 'r');
            ErrorHandler::stop();
            if (! $file) {
                return false;
            }
        } else {
            fseek($file, 0);
        }

        $result = false;

        $line = fgets($file) ?: '';
        if (str_starts_with($line, 'From ')) {
            $result = true;
        }

        if ($fileIsString) {
            ErrorHandler::start(E_WARNING);
            fclose($file);
            ErrorHandler::stop();
        }

        return $result;
    }

    /**
     * open given file as current mbox file
     *
     * @param  string $filename filename of mbox file
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    protected function openMboxFile($filename)
    {
        if ($this->fh) {
            $this->close();
        }

        if (is_dir($filename)) {
            throw new Exception\InvalidArgumentException('file is not a valid mbox file');
        }

        ErrorHandler::start();
        $this->fh = fopen($filename, 'r');
        $error    = ErrorHandler::stop();
        if (! $this->fh) {
            throw new Exception\RuntimeException('cannot open mbox file', 0, $error);
        }
        $this->filename  = $filename;
        $this->filemtime = filemtime($this->filename);

        if (! $this->isMboxFile($this->fh, false)) {
            ErrorHandler::start(E_WARNING);
            fclose($this->fh);
            $error = ErrorHandler::stop();
            throw new Exception\InvalidArgumentException('file is not a valid mbox format', 0, $error);
        }

        $messagePos = ['start' => ftell($this->fh), 'separator' => 0, 'end' => 0];
        while (($line = fgets($this->fh)) !== false) {
            if (str_starts_with($line, 'From ')) {
                $messagePos['end'] = ftell($this->fh) - strlen($line) - 2; // + newline
                if (! $messagePos['separator']) {
                    $messagePos['separator'] = $messagePos['end'];
                }
                $this->positions[] = $messagePos;
                $messagePos        = ['start' => ftell($this->fh), 'separator' => 0, 'end' => 0];
            }
            if (! $messagePos['separator'] && ! trim($line)) {
                $messagePos['separator'] = ftell($this->fh);
            }
        }

        $messagePos['end'] = ftell($this->fh);
        if (! $messagePos['separator']) {
            $messagePos['separator'] = $messagePos['end'];
        }
        $this->positions[] = $messagePos;
    }

    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     */
    public function close()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
        $this->positions = [];
    }

    /**
     * Waste some CPU cycles doing nothing.
     *
     * @return bool always return true
     */
    public function noop()
    {
        return true;
    }

    /**
     * stub for not supported message deletion
     *
     * @param int $id message number
     * @throws Exception\RuntimeException
     */
    public function removeMessage($id)
    {
        throw new Exception\RuntimeException('mbox is read-only');
    }

    /**
     * get unique id for one or all messages
     *
     * Mbox does not support unique ids (yet) - it's always the same as the message number.
     * That shouldn't be a problem, because we can't change mbox files. Therefor the message
     * number is save enough.
     *
     * @param int|null $id message number
     * @return array|string message number for given message or all messages as array
     * @throws ExceptionInterface
     */
    public function getUniqueId($id = null)
    {
        if ($id) {
            // check if id exists
            $this->getPos($id);
            return $id;
        }

        $range = range(1, $this->countMessages());
        return array_combine($range, $range);
    }

    /**
     * get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @return int message number
     * @throws ExceptionInterface
     */
    public function getNumberByUniqueId($id)
    {
        // check if id exists
        $this->getPos($id);
        return $id;
    }

    /**
     * magic method for serialize()
     *
     * with this method you can cache the mbox class
     *
     * @return array name of variables
     */
    public function __sleep()
    {
        return ['filename', 'positions', 'filemtime'];
    }

    /**
     * magic method for unserialize()
     *
     * with this method you can cache the mbox class
     * for cache validation the mtime of the mbox file is used
     *
     * @throws Exception\RuntimeException
     */
    public function __wakeup()
    {
        ErrorHandler::start();
        $filemtime = filemtime($this->filename);
        ErrorHandler::stop();
        if ($this->filemtime != $filemtime) {
            $this->close();
            $this->openMboxFile($this->filename);
        } else {
            ErrorHandler::start();
            $this->fh = fopen($this->filename, 'r');
            $error    = ErrorHandler::stop();
            if (! $this->fh) {
                throw new Exception\RuntimeException('cannot open mbox file', 0, $error);
            }
        }
    }
}
