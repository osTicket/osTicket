<?php

namespace Laminas\Crypt\Symmetric;

use Psr\Container\ContainerInterface;

use function array_key_exists;
use function sprintf;

/**
 * Plugin manager implementation for the padding adapter instances.
 *
 * Enforces that padding adapters retrieved are instances of
 * Padding\PaddingInterface. Additionally, it registers a number of default
 * padding adapters available.
 */
class PaddingPluginManager implements ContainerInterface
{
    /** @var array<string, string> */
    private $paddings = [
        'pkcs7'     => Padding\Pkcs7::class,
        'nopadding' => Padding\NoPadding::class,
        'null'      => Padding\NoPadding::class,
    ];

    /**
     * Do we have the padding plugin?
     *
     * @param  string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->paddings);
    }

    /**
     * Retrieve the padding plugin
     *
     * @param  string $id
     * @return Padding\PaddingInterface
     */
    public function get($id)
    {
        if (! $this->has($id)) {
            throw new Exception\NotFoundException(sprintf(
                "The padding adapter %s does not exist",
                $id
            ));
        }
        $class = $this->paddings[$id];
        return new $class();
    }
}
