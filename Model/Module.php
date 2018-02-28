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



namespace Mirasvit\Core\Model;

use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Dir\Reader as DirReader;

class Module
{
    /**
     * @var array
     */
    private static $modules = null;

    /**
     * @var FullModuleList
     */
    protected $fullModuleList;

    /**
     * @var DirReader
     */
    protected $dirReader;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var string
     */
    protected $installedVersion;

    /**
     * @var string
     */
    protected $latestVersion;

    /**
     * @var string
     */
    protected $url;

    public function __construct(
        FullModuleList $fullModuleList,
        DirReader $dirReader
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->dirReader = $dirReader;
    }

    /**
     * @return array
     */
    public function getAllModules()
    {
        if (self::$modules == null) {
            self::$modules = json_decode(@file_get_contents('http://mirasvit.com/pc/modules/'), true);

            if (!is_array(self::$modules)) {
                self::$modules = [];
            }
        }

        return self::$modules;
    }

    /**
     * @return array
     */
    public function getInstalledModules()
    {
        $modules = [];
        foreach ($this->fullModuleList->getAll() as $module) {
            if (substr($module['name'], 0, strlen('Mirasvit_')) == 'Mirasvit_') {
                $modules[] = $module['name'];
            }
        }

        return $modules;
    }

    /**
     * @param string $moduleName
     * @return $this
     */
    public function load($moduleName)
    {
        $modules = $this->getAllModules();

        if (array_key_exists(strtolower($moduleName), $modules)) {

            $m = $modules[strtolower($moduleName)];

            $this->moduleName = $moduleName;
            $this->name = $m['name'];
            $this->latestVersion = $m['version'];
            $this->url = $m['url'];

            $composer = $this->getComposerInformation($moduleName);

            if ($composer) {
                $this->installedVersion = $composer['version'];
            }
        }

        return $this;
    }

    /**
     * @param string $moduleName
     * @return array|false
     */
    public function getComposerInformation($moduleName)
    {
        $dir = $this->dirReader->getModuleDir("", $moduleName);

        if (file_exists($dir . '/composer.json')) {
            return json_decode(file_get_contents($dir . '/composer.json'), true);
        }

        if (file_exists($dir . '/../../composer.json')) {
            return json_decode(file_get_contents($dir . '/../../composer.json'), true);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @return string
     */
    public function getInstalledVersion()
    {
        return $this->installedVersion;
    }

    /**
     * @return string
     */
    public function getLatestVersion()
    {
        return $this->latestVersion;
    }

    /**
     * @return bool
     */
    public function isMetaPackage()
    {
        if ($this->moduleName == 'Mirasvit_SearchUltimate'
            || $this->moduleName == 'Mirasvit_SearchElasticUltimate') {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Recursively retrieve names of the required Mirasvit modules for given $moduleName.
     *
     * @param string $moduleName
     *
     * @return string[]
     */
    public function getRequiredModuleNames($moduleName)
    {
        return $this->requiredModuleNames($moduleName, []);
    }

    /**
     * @param string $moduleName
     * @param array $modules
     * @return array
     */
    public function requiredModuleNames($moduleName, array $modules)
    {
        $composerInfo = $this->getComposerInformation($moduleName);
        if (!isset($composerInfo['require'])) {
            return $modules;
        }

        foreach (array_keys($composerInfo['require']) as $package) {
            if (strpos($package, "mirasvit/") === false) {
                continue;
            }
            foreach ($this->getInstalledModules() as $installedModule) {
                if (in_array($installedModule, $modules)) {
                    continue;
                }
                $installedModuleInfo = $this->getComposerInformation($installedModule);
                if (!$installedModuleInfo) {
                    continue;
                }
                if ($package == $installedModuleInfo['name']) {
                    $modules[] = $installedModule;
                    $modules = $this->requiredModuleNames($installedModule, $modules);
                }
            }
        }

        return $modules;
    }
}