<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.Interface.Suffix

namespace Laminas\Loader;

/**
 * Short name locator interface
 */
interface ShortNameLocator
{
    /**
     * Whether or not a Helper by a specific name
     *
     * @param  string $name
     * @return bool
     */
    public function isLoaded($name);

    /**
     * Return full class name for a named helper
     *
     * @param  string $name
     * @return string
     */
    public function getClassName($name);

    /**
     * Load a helper via the name provided
     *
     * @param  string $name
     * @return string
     */
    public function load($name);
}
