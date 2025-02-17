<?php

namespace Laminas\Mail\Protocol\Smtp\Auth;

use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Protocol\Smtp;

use function array_replace_recursive;
use function base64_decode;
use function base64_encode;
use function hash_hmac;
use function is_array;
use function is_string;

/**
 * Performs CRAM-MD5 authentication
 */
class Crammd5 extends Smtp
{
    /** @var non-empty-string|null */
    protected $username;

    /** @var non-empty-string|null */
    protected $password;

    /**
     * All parameters may be passed as an array to the first argument of the
     * constructor. If so,
     *
     * @param  string|array $host   (Default: 127.0.0.1)
     * @param  null|int     $port   (Default: null)
     * @param  null|array   $config Auth-specific parameters
     */
    public function __construct($host = '127.0.0.1', $port = null, $config = null)
    {
        // Did we receive a configuration array?
        $config     = $config ?? [];
        $origConfig = $config;
        if (is_array($host)) {
            // Merge config array with principal array, if provided
            $config = array_replace_recursive($host, $config);
        }

        if (isset($config['username'])) {
            $this->setUsername($config['username']);
        }
        if (isset($config['password'])) {
            $this->setPassword($config['password']);
        }

        // Call parent with original arguments
        parent::__construct($host, $port, $origConfig);
    }

    /**
     * Performs CRAM-MD5 authentication with supplied credentials
     */
    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();

        $this->_send('AUTH CRAM-MD5');
        $challenge = $this->_expect(334);
        $challenge = base64_decode($challenge);
        $digest    = $this->hmacMd5($this->getPassword(), $challenge);
        $this->_send(base64_encode($this->getUsername() . ' ' . $digest));
        $this->_expect(235);
        $this->auth = true;
    }

    /**
     * Set value for username
     *
     * @param  non-empty-string $username
     * @return Crammd5
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return non-empty-string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set value for password
     *
     * @param non-empty-string $password
     * @return Crammd5
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return non-empty-string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Prepare CRAM-MD5 response to server's ticket
     *
     * @param non-empty-string $key Challenge key (usually password)
     * @param non-empty-string $data  Challenge data
     * @param  int    $block Length of blocks (deprecated; unused)
     * @return string
     */
    protected function hmacMd5($key, $data, /** @deprecated  */ $block = 64)
    {
        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('CramMD5 authentication requires a non-empty password');
        }

        if (! is_string($data) || $data === '') {
            throw new InvalidArgumentException('CramMD5 authentication requires a non-empty challenge');
        }

        return hash_hmac('md5', $data, $key, false);
    }
}
