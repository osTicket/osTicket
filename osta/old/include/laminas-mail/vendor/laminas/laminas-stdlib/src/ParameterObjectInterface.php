<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

interface ParameterObjectInterface
{
    /**
     * @param string $key
     * @return void
     */
    public function __set($key, mixed $value);

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key);

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key);

    /**
     * @param string $key
     * @return void
     */
    public function __unset($key);
}
