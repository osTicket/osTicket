<?php

namespace Laminas\Validator;

/**
 * Hint to the laminas-modulemanager ServiceListener that a module provides validators.
 *
 * Module classes implementing this interface hint to
 * Laminas\ModuleManager\ServiceListener that they provide validators for the
 * ValidatorPluginManager.
 *
 * For users of laminas-mvc/laminas-modulemanager v2, this poses no backwards-compatibility
 * break as the method getValidatorConfig is still duck-typed within Laminas\Validator\Module
 * when providing configuration to the ServiceListener.
 */
interface ValidatorProviderInterface
{
    /**
     * Provide plugin manager configuration for validators.
     *
     * @return array
     */
    public function getValidatorConfig();
}
