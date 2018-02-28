<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-core
 * @version   1.2.60
 * @copyright Copyright (C) 2018 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Service;


use Mirasvit\Core\Api\Service\ValidationServiceInterface;
use Mirasvit\Core\Api\Service\ValidatorInterface;
use Mirasvit\Core\Model\ModuleFactory;
use Mirasvit\Core\Model\Module;

class ValidationService implements ValidationServiceInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @var ModuleFactory
     */
    private $moduleFactory;

    /**
     * ValidationService constructor.
     *
     * @param ModuleFactory        $moduleFactory
     * @param ValidatorInterface[] $validators
     */
    public function __construct(
        ModuleFactory $moduleFactory,
        array $validators = []
    ) {
        $this->moduleFactory = $moduleFactory;
        $this->validators = $validators;
    }

    /**
     * Validation run scenario:
     * 1. Run all validations if no modules passed.
     * 2. Run validation for every module dependency @see \Mirasvit\Core\Api\Service\ValidatorInterface::getModules()
     * 3. Run validation if a validator's module name matches a passed module name.
     *
     * {@inheritdoc}
     */
    public function runValidation(array $modules = [])
    {
        $result = [];
        foreach ($this->validators as $validator) {
            if ($this->canValidate($validator->getModuleName(), $modules)) {
                $result = array_merge($result, $validator->validate());
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     *
     */
    private function canValidate($validatorModuleName, array $requestedModules)
    {
        if (empty($requestedModules) || in_array($validatorModuleName, $requestedModules)) {
            return true;
        }

        /** @var Module $module */
        foreach ($requestedModules as $moduleName) {
            $module = $this->moduleFactory->create()->load($moduleName);
            $requiredModules = $module->getRequiredModuleNames($moduleName);
            if (in_array($validatorModuleName, $requiredModules)) {
                return true;
            }
        }

        return false;
    }
}
