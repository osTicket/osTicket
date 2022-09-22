<?php

namespace Laminas\Crypt\PublicKey\Rsa;

abstract class AbstractKey
{
    public const DEFAULT_KEY_SIZE = 2048;

    /**
     * PEM formatted key
     *
     * @var string
     */
    protected $pemString;

    /**
     * Key Resource
     *
     * @var resource
     */
    protected $opensslKeyResource;

    /**
     * Openssl details array
     *
     * @var array
     */
    protected $details = [];

    /**
     * Get key size in bits
     *
     * @return int
     */
    public function getSize()
    {
        return $this->details['bits'];
    }

    /**
     * Retrieve openssl key resource
     *
     * @return resource
     */
    public function getOpensslKeyResource()
    {
        return $this->opensslKeyResource;
    }

    /**
     * Encrypt using this key
     *
     * @abstract
     * @param string $data
     * @return string
     */
    abstract public function encrypt($data);

    /**
     * Decrypt using this key
     *
     * @abstract
     * @param string $data
     * @return string
     */
    abstract public function decrypt($data);

    /**
     * Get string representation of this key
     *
     * @abstract
     * @return string
     */
    abstract public function toString();

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
