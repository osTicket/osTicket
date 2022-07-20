<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Password;

use Laminas\Crypt\Hash;

/**
 * Bcrypt algorithm using crypt() function of PHP with password
 * hashed using SHA2 to allow for passwords >72 characters.
 */
class BcryptSha extends Bcrypt
{

    /**
     * BcryptSha
     *
     * @param  string $password
     * @throws Exception\RuntimeException
     * @return string
     */
    public function create($password)
    {
        return parent::create(Hash::compute('sha256', $password));
    }

    /**
     * Verify if a password is correct against a hash value
     *
     * @param  string $password
     * @param  string $hash
     * @throws Exception\RuntimeException when the hash is unable to be processed
     * @return bool
     */
    public function verify($password, $hash)
    {
        return parent::verify(Hash::compute('sha256', $password), $hash);
    }
}
