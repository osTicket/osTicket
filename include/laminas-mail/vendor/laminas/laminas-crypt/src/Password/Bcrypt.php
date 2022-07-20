<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Password;

use Laminas\Math\Rand;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function is_array;
use function mb_strlen;
use function microtime;
use function password_hash;
use function password_verify;
use function sprintf;
use function strtolower;
use function trigger_error;

use const E_USER_DEPRECATED;
use const PASSWORD_BCRYPT;
use const PHP_VERSION_ID;

/**
 * Bcrypt algorithm using crypt() function of PHP
 */
class Bcrypt implements PasswordInterface
{
    const MIN_SALT_SIZE = 22;

    /**
     * @var string
     */
    protected $cost = '10';

    /**
     * @var string
     */
    protected $salt;

    /**
     * Constructor
     *
     * @param array|Traversable $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = [])
    {
        if (! empty($options)) {
            if ($options instanceof Traversable) {
                $options = ArrayUtils::iteratorToArray($options);
            }

            if (! is_array($options)) {
                throw new Exception\InvalidArgumentException(
                    'The options parameter must be an array or a Traversable'
                );
            }

            foreach ($options as $key => $value) {
                switch (strtolower($key)) {
                    case 'salt':
                        $this->setSalt($value);
                        break;
                    case 'cost':
                        $this->setCost($value);
                        break;
                }
            }
        }
    }

    /**
     * Bcrypt
     *
     * @param  string $password
     * @throws Exception\RuntimeException
     * @return string
     */
    public function create($password)
    {
        $options = [ 'cost' => (int) $this->cost ];
        if (PHP_VERSION_ID < 70000) { // salt is deprecated from PHP 7.0
            $salt = $this->salt ?: Rand::getBytes(self::MIN_SALT_SIZE);
            $options['salt'] = $salt;
        }
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Verify if a password is correct against a hash value
     *
     * @param  string $password
     * @param  string $hash
     * @return bool
     */
    public function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Set the cost parameter
     *
     * @param  int|string $cost
     * @throws Exception\InvalidArgumentException
     * @return Bcrypt Provides a fluent interface
     */
    public function setCost($cost)
    {
        if (! empty($cost)) {
            $cost = (int) $cost;
            if ($cost < 4 || $cost > 31) {
                throw new Exception\InvalidArgumentException(
                    'The cost parameter of bcrypt must be in range 04-31'
                );
            }
            $this->cost = sprintf('%1$02d', $cost);
        }
        return $this;
    }

    /**
     * Get the cost parameter
     *
     * @return string
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set the salt value
     *
     * @param  string $salt
     * @throws Exception\InvalidArgumentException
     * @return Bcrypt Provides a fluent interface
     */
    public function setSalt($salt)
    {
        if (PHP_VERSION_ID >= 70000) {
            trigger_error('Salt support is deprecated starting with PHP 7.0.0', E_USER_DEPRECATED);
        }

        if (mb_strlen($salt, '8bit') < self::MIN_SALT_SIZE) {
            throw new Exception\InvalidArgumentException(
                'The length of the salt must be at least ' . self::MIN_SALT_SIZE . ' bytes'
            );
        }

        $this->salt = $salt;
        return $this;
    }

    /**
     * Get the salt value
     *
     * @return string
     */
    public function getSalt()
    {
        if (PHP_VERSION_ID >= 70000) {
            trigger_error('Salt support is deprecated starting with PHP 7.0.0', E_USER_DEPRECATED);
        }

        return $this->salt;
    }

    /**
     * Benchmark the bcrypt hash generation to determine the cost parameter based on time to target.
     *
     * The default time to test is 50 milliseconds which is a good baseline for
     * systems handling interactive logins. If you increase the time, you will
     * get high cost with better security, but potentially expose your system
     * to DoS attacks.
     *
     * @see php.net/manual/en/function.password-hash.php#refsect1-function.password-hash-examples
     * @param float $timeTarget Defaults to 50ms (0.05)
     * @return int Maximum cost value that falls within the time to target.
     */
    public function benchmarkCost($timeTarget = 0.05)
    {
        $cost = 8;

        do {
            $cost++;
            $start = microtime(true);
            password_hash('test', PASSWORD_BCRYPT, [ 'cost' => $cost ]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);

        return $cost;
    }
}
