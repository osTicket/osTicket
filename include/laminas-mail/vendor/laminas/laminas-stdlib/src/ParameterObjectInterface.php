<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

/**
 * @template TKey of string
 * @template TValue
 */
interface ParameterObjectInterface
{
    /**
     * @param TKey $key
     * @param TValue|null $value
     * @return void
     */
    public function __set($key, mixed $value);

    /**
     * @param TKey $key
     * @return TValue
     */
    public function __get($key);

    /**
     * @param TKey $key
     * @return bool
     */
    public function __isset($key);

    /**
     * @param TKey $key
     * @return void
     */
    public function __unset($key);
}
