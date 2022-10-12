<?php

namespace Laminas\Crypt;

use Psr\Container\ContainerInterface;

use function array_key_exists;
use function sprintf;

/**
 * Plugin manager implementation for the symmetric adapter instances.
 *
 * Enforces that symmetric adapters retrieved are instances of
 * Symmetric\SymmetricInterface. Additionally, it registers a number of default
 * symmetric adapters available.
 */
class SymmetricPluginManager implements ContainerInterface
{
    /**
     * Default set of symmetric adapters
     *
     * @var array
     */
    protected $symmetric = [
        'mcrypt'  => Symmetric\Mcrypt::class,
        'openssl' => Symmetric\Openssl::class,
    ];

    /**
     * Do we have the symmetric plugin?
     *
     * @param  string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->symmetric);
    }

    /**
     * Retrieve the symmetric plugin
     *
     * @param  string $id
     * @return Symmetric\SymmetricInterface
     */
    public function get($id)
    {
        if (! $this->has($id)) {
            throw new Exception\NotFoundException(sprintf(
                'The symmetric adapter %s does not exist',
                $id
            ));
        }
        $class = $this->symmetric[$id];
        return new $class();
    }
}
