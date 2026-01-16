<?php

namespace Laminas\Mail\Protocol\Smtp\Auth;

use Laminas\Mail\Protocol\Smtp;
use Laminas\Mail\Protocol\Xoauth2\Xoauth2 as Xoauth2AuthEncoder;

use function array_replace_recursive;
use function is_array;

/**
 * Performs Xoauth2 authentication
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Xoauth2 extends Smtp
{
    /**
     * SMTP username
     *
     * @var string
     */
    protected $username;

    /**
     * Xoauth2 access token
     *
     * @var string
     */
    protected $accessToken;

    /**
     * @param string|array $host (Default: 127.0.0.1)
     * @param int|null $port (Default: null)
     * @param array|null $config Auth-specific parameters
     */
    public function __construct($host = '127.0.0.1', $port = null, ?array $config = null)
    {
        // Did we receive a configuration array?
        $origConfig = $config;
        if (is_array($host)) {
            // Merge config array with principal array, if provided
            if (is_array($config)) {
                $config = array_replace_recursive($host, $config);
            } else {
                $config = $host;
            }
        }

        if (is_array($config)) {
            if (isset($config['username'])) {
                $this->setUsername((string) $config['username']);
            }
            if (isset($config['access_token'])) {
                $this->setAccessToken((string) $config['access_token']);
            }
        }

        // Call parent with original arguments
        parent::__construct($host, $port, $origConfig);
    }

    /**
     * Perform XOAUTH2 authentication with supplied credentials
     *
     * @return void
     */
    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();

        $this->_send('AUTH XOAUTH2');
        $this->_expect('334');
        $this->_send(Xoauth2AuthEncoder::encodeXoauth2Sasl($this->getUsername(), $this->getAccessToken()));
        $this->_expect('235');
        $this->auth = true;
    }

    /**
     * Set value for username
     *
     * @param string $username
     * @return Xoauth2
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set value for access token
     *
     * @param string $token
     * @return Xoauth2
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
        return $this;
    }

    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
