<?php

/**
 * @see       https://github.com/laminas/laminas-config for the canonical source repository
 * @copyright https://github.com/laminas/laminas-config/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-config/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Config\Processor;

class Constant extends Token implements ProcessorInterface
{
    /**
     * Replace only user-defined tokens
     *
     * @var bool
     */
    protected $userOnly = true;

    /**
     * Constant Processor walks through a Config structure and replaces all
     * PHP constants with their respective values
     *
     * @param bool   $userOnly              True to process only user-defined constants, false to process all PHP constants
     * @param string $prefix                Optional prefix
     * @param string $suffix                Optional suffix
     * @return \Laminas\Config\Processor\Constant
     */
    public function __construct($userOnly = true, $prefix = '', $suffix = '')
    {
        $this->setUserOnly($userOnly);
        $this->setPrefix($prefix);
        $this->setSuffix($suffix);

        $this->loadConstants();
    }

    /**
     * @return bool
     */
    public function getUserOnly()
    {
        return $this->userOnly;
    }

    /**
     * Should we use only user-defined constants?
     *
     * @param  bool $userOnly
     * @return Constant
     */
    public function setUserOnly($userOnly)
    {
        $this->userOnly = (bool) $userOnly;
        return $this;
    }

    /**
     * Load all currently defined constants into parser.
     *
     * @return void
     */
    public function loadConstants()
    {
        if ($this->userOnly) {
            $constants = get_defined_constants(true);
            $constants = isset($constants['user']) ? $constants['user'] : [];
            $this->setTokens($constants);
        } else {
            $this->setTokens(get_defined_constants());
        }
    }

    /**
     * Get current token registry.
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }
}
