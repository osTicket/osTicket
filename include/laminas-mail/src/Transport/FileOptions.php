<?php

namespace Laminas\Mail\Transport;

use Laminas\Mail\Exception;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Stdlib\AbstractOptions;

use function gettype;
use function is_callable;
use function is_dir;
use function is_object;
use function is_writable;
use function mt_rand;
use function sprintf;
use function sys_get_temp_dir;
use function time;

class FileOptions extends AbstractOptions
{
    /** @var string Path to stored mail files */
    protected $path;

    /** @var callable */
    protected $callback;

    /**
     * Set path to stored mail files
     *
     * @param  string $path
     * @throws InvalidArgumentException
     * @return FileOptions
     */
    public function setPath($path)
    {
        if (! is_dir($path) || ! is_writable($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a valid path in which to write mail files; received "%s"',
                __METHOD__,
                (string) $path
            ));
        }
        $this->path = $path;
        return $this;
    }

    /**
     * Get path
     *
     * If none is set, uses value from sys_get_temp_dir()
     *
     * @return string
     */
    public function getPath()
    {
        if (null === $this->path) {
            $this->setPath(sys_get_temp_dir());
        }
        return $this->path;
    }

    /**
     * Set callback used to generate a file name
     *
     * @param  callable $callback
     * @throws InvalidArgumentException
     * @return FileOptions
     */
    public function setCallback($callback)
    {
        if (! is_callable($callback)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a valid callback; received "%s"',
                __METHOD__,
                is_object($callback) ? $callback::class : gettype($callback)
            ));
        }
        $this->callback = $callback;
        return $this;
    }

    /**
     * Get callback used to generate a file name
     *
     * @return callable
     */
    public function getCallback()
    {
        if (null === $this->callback) {
            $this->setCallback(static fn() => 'LaminasMail_' . time() . '_' . mt_rand() . '.eml');
        }
        return $this->callback;
    }
}
