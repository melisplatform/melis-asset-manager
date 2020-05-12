<?php
/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisAssetManager\Service;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackage;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MelisModulesService implements ServiceLocatorAwareInterface
{
    /** @var \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator */
    public $serviceLocator;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * Returns the module name, module package, and its' version
     *
     * @param null $moduleName - provide the module name if you want to get the package specific information
     *
     * @return array
     */
    public function getModulesAndVersions($moduleName = null)
    {
        $tmpModules = [];
        $repos = $this->getComposer()->getRepositoryManager()->getLocalRepository();

        $composerFile = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/composer/installed.json';
        $composer = (array) \Zend\Json\Json::decode(file_get_contents($composerFile));


        foreach ($composer as $package) {
            $packageModuleName = isset($package->extra) ? (array) $package->extra : null;
            $module = null;
            if (isset($packageModuleName['module-name'])) {
                $module = $packageModuleName['module-name'];
            }

            if ($module) {
                $tmpModules[$module] = [
                    'package' => $package->name,
                    'module' => $module,
                    'version' => $package->version,
                ];

                if ($module == $moduleName) {
                    break;
                }
            }
        }

        $userModules = $this->getUserModules();
        $exclusions = ['MelisModuleConfig', 'MelisSites'];

        foreach ($userModules as $module) {
            if (!in_array($module, $exclusions)) {
                $class = $_SERVER['DOCUMENT_ROOT'] . '/../module/' . $module . '/Module.php';
                $class = file_get_contents($class);

                $package = $module;
                $version = '1.0';

                if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $class, $matches)) {

                    $result = array_combine($matches[1], $matches[2]);
                    $version = isset($result['version']) ? $result['version'] : '1.0';
                    $package = isset($result['module']) ? $result['module'] : $module;

                }
                $tmpModules[$package] = [
                    'package' => $package,
                    'module' => $package,
                    'version' => $version,
                ];

            }
        }


        $modules = $tmpModules;


        if (!is_null($moduleName)) {
            return isset($modules[$moduleName]) ? $modules[$moduleName] : null;
        }

        return $modules;
    }

    /**
     * @return \Composer\Composer
     */
    public function getComposer()
    {
        if (is_null($this->composer)) {
            $composer = new \MelisComposerDeploy\MelisComposer();
            $this->composer = $composer->getComposer();
        }

        return $this->composer;
    }

    /**
     * @param Composer $composer
     *
     * @return $this
     */
    public function setComposer(Composer $composer)
    {
        $this->composer = $composer;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserModules()
    {
        $userModules = $_SERVER['DOCUMENT_ROOT'] . '/../module';

        $modules = [];
        if ($this->checkDir($userModules)) {
            $modules = $this->getDir($userModules);
        }

        return $modules;
    }

    /**
     * This will check if directory exists and it's a valid directory
     *
     * @param $dir
     *
     * @return bool
     */
    protected function checkDir($dir)
    {
        if (file_exists($dir) && is_dir($dir)) {
            return true;
        }

        return false;
    }

    /**
     * Returns all the sub-folders in the provided path
     *
     * @param String $dir
     * @param array $excludeSubFolders
     *
     * @return array
     */
    protected function getDir($dir, $excludeSubFolders = [])
    {
        $directories = [];
        if (file_exists($dir)) {
            $excludeDir = array_merge(['.', '..', '.gitignore'], $excludeSubFolders);
            $directory = array_diff(scandir($dir), $excludeDir);

            foreach ($directory as $d) {
                if (is_dir($dir . '/' . $d)) {
                    $directories[] = $d;
                }
            }

        }

        return $directories;
    }

    /**
     * @return array
     */
    public function getSitesModules()
    {
        $userModules = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';

        $modules = [];
        if ($this->checkDir($userModules)) {
            $modules = $this->getDir($userModules);
        }

        return $modules;
    }

    public static function modulesConfigPath()
    {
        $appConfig = $_SERVER['DOCUMENT_ROOT'] . '/../config/';

        $modulesPathConfig = $appConfig . 'melis.modules.path.php';

        if (file_exists($modulesPathConfig))
            return require $modulesPathConfig;

        return null;
    }

    /**
     * Returns all the modules that has been created by Melis
     *
     * @return array
     */
    public function getMelisModules()
    {
        $modules = [];
        foreach ($this->getAllModules() as $module) {
            if (strpos($module, 'Melis') !== false || strpos($module, 'melis') !== false) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Returns all the modules
     */
    public function getAllModules($composerPackages = false)
    {
        if ($composerPackages){
            $repos = array_merge($this->getUserModules(), $this->getVendorModules());
        } else
            return array_keys(self::modulesConfigPath());
    }

    /**
     * Returns all melisplatform-module packages loaded by composer
     * @return array
     */
    public function getVendorModules()
    {
        $packagesCacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../cache/composer_packages/';
        $melisPackages = $packagesCacheDir . 'melis_packages.dat';

        if (file_exists($melisPackages)) {
            $modules = unserialize(file_get_contents($melisPackages));
        } else {
            $repos = $this->getComposer()->getRepositoryManager()->getLocalRepository();
            $repoPackages = $repos->getPackages();

            $packages = array_filter($repoPackages, function ($package) {
                /** @var CompletePackage $package */
                return $package->getType() === 'melisplatform-module' &&
                    array_key_exists('module-name', $package->getExtra());
            });
    
            $modules = array_map(function ($package) {
                /** @var CompletePackage $package */
                return $package->getExtra()['module-name'];
            }, $packages);
    
            sort($modules);

            try {
                if (!is_dir($packagesCacheDir)) {
                    mkdir($packagesCacheDir, 0777);
                }

                $fd = fopen($melisPackages, 'w');
                if ($fd) {
                    fwrite($fd, serialize($modules));
                    fclose($fd);
                    chmod($melisPackages, 0777);
                } else {
                    /*echo "Error generating file $modulePathFile : check rights";
                    die;*/
                }
            } catch (\Exception $e) {
                /*echo "Error generating file $modulePathFile : check rights";
                die;*/
            }
        }

        return $modules;
    }

    /**
     * Returns an array of modules or packages that is dependent to the module name provided
     *
     * @param $moduleName
     * @param bool $convertPackageNameToNamespace
     * @param bool $getOnlyActiveModules - returns only the active modules
     *
     * @return array
     */
    public function getChildDependencies($moduleName, $convertPackageNameToNamespace = true, $getOnlyActiveModules = true)
    {
        $modules = $this->getAllModules();
        $matchModule = $convertPackageNameToNamespace ? $moduleName : $this->convertToPackageName($moduleName);
        $dependents = [];

        foreach ($modules as $module) {
            $dependencies = $this->getDependencies($module, $convertPackageNameToNamespace);

            if ($dependencies) {
                if (in_array($matchModule, $dependencies)) {
                    $dependents[] = $convertPackageNameToNamespace ? $module : $this->convertToPackageName($module);
                }
            }
        }

        if (true === $getOnlyActiveModules) {

            $activeModules = $this->getActiveModules();
            $modules = [];

            foreach ($dependents as $module) {
                $modules[] = $module;
            }

            $dependents = $modules;
        }

        return $dependents;
    }

    /**
     * convert module name into package name, example: MelisCore will become melis-core
     *
     * @param $module
     *
     * @return string
     */
    private function convertToPackageName($module)
    {
        $moduleName = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $module));

        return $moduleName;
    }

    /**
     * Returns the dependencies of the module
     *
     * @param $moduleName
     * @param bool $convertPackageNameToNamespace - set to "true" to convert all package name into their actual Module name
     *
     * @return array
     */
    public function getDependencies($moduleName, $convertPackageNameToNamespace = true)
    {
        $modulePath = $this->getComposerModulePath($moduleName);
        $dependencies = [];

        if ($modulePath) {

            $defaultDependencies = ['melis-core'];
            $dependencies = $defaultDependencies;
            $composerPossiblePath = [$modulePath . '/composer.json'];
            $composerFile = null;

            // search for the composer.json file
            foreach ($composerPossiblePath as $file) {
                if (file_exists($file)) {
                    $composerFile = file_get_contents($file);
                }
            }

            // if composer.json is found
            if ($composerFile) {

                $composer = json_decode($composerFile, true);
                $requires = isset($composer['require']) ? $composer['require'] : null;
                if ($requires) {
                    $requires = array_map(function ($a) {
                        // remove melisplatform prefix
                        return str_replace(['melisplatform/', ' '], '', trim($a));
                    }, array_keys($requires));

                    $dependencies = $requires;
                }
            }

            if ($convertPackageNameToNamespace) {
                $tmpDependencies = [];
                $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');

                foreach ($dependencies as $dependency) {
                    $tmpDependencies[] = ucfirst($toolSvc->convertToNormalFunction($dependency));
                }

                $dependencies = $tmpDependencies;
            }
        }

        return $dependencies;
    }

    /**
     * Returns the full path of the module
     *
     * @param $moduleName
     * @param bool $returnFullPath
     *
     * @return string
     */
    public static function getModulePath($module, $relativePath = false) 
    {
        // Modules path from config 
        $modulesPath = self::modulesConfigPath();

        if (is_null($modulesPath))
            return;

        if (!empty($modulesPath[$module])) {
            if (!$relativePath) {
                return $modulesPath[$module];
            } else {
                return $_SERVER['DOCUMENT_ROOT'].'/..'.$modulesPath[$module];
            }
        }
    }

    public function getUserModulePath($moduleName, $returnFullPath = true)
    {
        $path = '';
        $userModules = $_SERVER['DOCUMENT_ROOT'] . '/../';

        if (in_array($moduleName, $this->getUserModules())) {
            if ($this->checkDir($userModules . 'module/' . $moduleName)) {
                if (!$returnFullPath) {
                    $path = '/module/' . $moduleName;
                } else {
                    $path = $userModules . 'module/' . $moduleName;
                }
            }
        }

        return $path;
    }

    public function getComposerModulePath($moduleName, $returnFullPath = true)
    {
        $modulesPathsConfig = self::modulesConfigPath();

        if (!is_null($modulesPathsConfig)) {
            return self::getModulePath($moduleName, $returnFullPath);
        } else {

            $composer = new \MelisComposerDeploy\MelisComposer();
            return $composer->getComposerModulePath($moduleName, $returnFullPath);
        }

        return '';
    }

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sl
     *
     * @return $this
     */
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;

        return $this;
    }

    /**
     * Returns all the modules that has been loaded in zend
     *
     * @param array $exclude
     *
     * @return array
     */
    public function getActiveModules($exclude = [])
    {
        $mm = $this->getServiceLocator()->get('ModuleManager');
        $loadedModules = array_keys($mm->getLoadedModules());
        $pluginModules = $this->getModulePlugins();
        $modules = [];
        foreach ($loadedModules as $module) {
            if (in_array($module, $pluginModules)) {
                if (!in_array($module, $exclude)) {
                    $modules[] = $module;
                }
            }
        }

        return $modules;
    }

    /**
     * Returns all modules plugins that does not belong or treated as core modules
     *
     * @param array $excludeModulesOnReturn
     *
     * @return array
     */
    public function getModulePlugins($excludeModulesOnReturn = [])
    {
        $modules = [];
        $excludeModules = array_values($this->getCoreModules());
        foreach ($this->getAllModules() as $module) {
            if (!in_array($module, array_merge($excludeModules, $excludeModulesOnReturn))) {
                $modules[] = $module;
            }
        }

        return $modules;

    }

    /**
     * Returns all the important modules
     *
     * @param array $excludeModulesOnReturn | exclude some modules that you don't want to be included in return
     *
     * @return array
     */
    public function getCoreModules($excludeModulesOnReturn = [])
    {
        $modules = [
            'melisdbdeploy' => 'MelisDbDeploy',
            'meliscomposerdeploy' => 'MelisComposerDeploy',
            'meliscore' => 'MelisCore',
            'melissites' => 'MelisSites',
            'melisassetmanager' => 'MelisAssetManager',
        ];

        if ($excludeModulesOnReturn) {
            foreach ($excludeModulesOnReturn as $exMod) {
                if (isset($modules[$exMod]) && $modules[$exMod]) {
                    unset($modules[$exMod]);
                }
            }
        }

        return $modules;
    }

    /**
     * This method activating a single module
     * and store to the module.load.php of the platform
     *
     * @param $module
     *
     * @return bool
     */
    public function activateModule(
        $module,
        $defaultModules = ['MelisAssetManager', 'MelisComposerDeploy', 'MelisDbDeploy', 'MelisCore'],
        $excludeModule = ['MelisModuleConfig'])
    {
        // Default melis modules
        $activeModules = $this->getActiveModules($defaultModules);

        // Removing "MelisModuleConfig" if exist on activated modules
        foreach ($activeModules as $key => $mod) {
            if (in_array($mod, $excludeModule)) {
                unset($activeModules[$key]);
            }
        }

        array_push($activeModules, $module);

        // Creating/updating module.load.php including new module
        return $this->createModuleLoader('config/', $activeModules, $defaultModules);
    }

    /**
     * Creates module loader file
     *
     * @param $pathToStore
     * @param array $modules
     * @param array $topModules
     * @param array $bottomModules
     *
     * @return bool
     */
    public function createModuleLoader($pathToStore, $modules = [],
                                       $topModules = ['melisdbdeploy', 'meliscomposerdeploy', 'meliscore'],
                                       $bottomModules = ['MelisModuleConfig'])
    {
        $tmpFileName = 'melis.module.load.php.tmp';
        $fileName = 'melis.module.load.php';
        if ($this->checkDir($pathToStore)) {
            $coreModules = $this->getCoreModules();
            $topModules = array_reverse($topModules);
            foreach ($topModules as $module) {
                if (isset($coreModules[$module]) && $coreModules[$module]) {
                    array_unshift($modules, $coreModules[$module]);
                } else {
                    array_unshift($modules, $module);
                }

            }

            foreach ($bottomModules as $module) {
                if (isset($coreModules[$module]) && $coreModules[$module]) {
                    array_push($modules, $coreModules[$module]);
                } else {
                    array_push($modules, $module);
                }
            }

            $config = new Config($modules, true);
            $writer = new PhpArray();
            $conf = $writer->toString($config);
            $conf = preg_replace('/    \d+/u', '', $conf); // remove the number index
            $conf = str_replace('=>', '', $conf); // remove the => characters.
            file_put_contents($pathToStore . '/' . $tmpFileName, $conf);

            if (file_exists($pathToStore . '/' . $tmpFileName)) {
                // check if the array is not empty
                $checkConfig = include($pathToStore . '/' . $tmpFileName);
                if (count($checkConfig) > 1) {
                    // delete the current module loader file
                    unlink($pathToStore . '/' . $fileName);
                    // rename the module loader tmp file into module.load.php
                    rename($pathToStore . '/' . $tmpFileName, $pathToStore . '/' . $fileName);
                    // Adding permission access
                    chmod($pathToStore . '/' . $fileName, 0777);
                    // if everything went well
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * @param $module
     *
     * @return bool
     */
    public function loadModule($module)
    {
        $trgtModule = $module;
        if (is_array($module) && !empty($module)) {
            $trgtModule = $module[count($module)-1];
        }

        if (!in_array($trgtModule, $this->getActiveModules())) {
            $moduleLoadFile = $_SERVER['DOCUMENT_ROOT'] . '/../config/melis.module.load.php';
            if (file_exists($moduleLoadFile)) {
                $modules = include $_SERVER['DOCUMENT_ROOT'] . '/../config/melis.module.load.php';

                $moduleCount = count($modules);
                $insertAtIdx = $moduleCount - 1;
                array_splice($modules, $insertAtIdx, 0, $module);

                // create the module.load file
                $this->createModuleLoader('config/', $modules, [], []);
            }
        }

        return $this->isModuleLoaded($trgtModule);
    }

    /**
     * @param $module
     *
     * @return bool
     */
    public function unloadModule($module)
    {
        $modules = $module;
        if (!is_array($module)) {
            $modules = [];
            $modules[] = $module;
        }

        $moduleLoadFile = $_SERVER['DOCUMENT_ROOT'] . '/../config/melis.module.load.php';

        if (file_exists($moduleLoadFile)) {
            $loadModules = include $moduleLoadFile;

            foreach ($loadModules as $idx => $loadedModule) {
                if (in_array($loadedModule, $modules)) {
                    unset($loadModules[$idx]);
                }
            }

            $this->createModuleLoader('config/', $loadModules, [], []);
        }

        return false;
    }

    /**
     * @param $module
     *
     * @return bool
     */
    public function isModuleLoaded($module)
    {
        $modules = include $_SERVER['DOCUMENT_ROOT'] . '/../config/melis.module.load.php';
        if (in_array($module, $modules)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $module
     *
     * @return bool
     */
    public function isSiteModule($module)
    {
        $repos = $this->getComposer()->getRepositoryManager()->getLocalRepository();

        $composerFile = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/composer/installed.json';
        $composer = (array) \Zend\Json\Json::decode(file_get_contents($composerFile));

        $repo = null;

        foreach ($composer as $package) {
            $packageModuleName = isset($package->extra) ? (array) $package->extra : null;

            if (isset($packageModuleName['module-name']) && $packageModuleName['module-name'] == $module) {
                $repo = (array) $package->extra;
                break;
            }
        }

        if (isset($repo['melis-site'])) {
            return (bool) $repo['melis-site'] ?? false;
        }

        return false;
    }
}
