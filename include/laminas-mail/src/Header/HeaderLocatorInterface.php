<?php

declare(strict_types=1);

namespace Laminas\Mail\Header;

/**
 * Interface detailing how to resolve header names to classes.
 */
interface HeaderLocatorInterface
{
    /**
     * @param class-string<HeaderInterface>|null $default
     * @return class-string<HeaderInterface>|null
     */
    public function get(string $name, ?string $default = null): ?string;

    public function has(string $name): bool;

    public function add(string $name, string $class): void;

    public function remove(string $name): void;
}
