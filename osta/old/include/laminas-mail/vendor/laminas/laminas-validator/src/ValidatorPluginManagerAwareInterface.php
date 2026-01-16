<?php

namespace Laminas\Validator;

interface ValidatorPluginManagerAwareInterface
{
    /**
     * Set validator plugin manager
     */
    public function setValidatorPluginManager(ValidatorPluginManager $pluginManager);

    /**
     * Get validator plugin manager
     *
     * @return ValidatorPluginManager
     */
    public function getValidatorPluginManager();
}
