<?php

namespace MelisAssetManager\Service;

use MelisAssetManager\View\Helper\MelisHeadPluginHelper;
use Laminas\ServiceManager\ServiceManager;
use MelisCore\Controller\ModulesController;
use MelisCore\Service\MelisServiceManager;

class MelisWebPackService extends MelisServiceManager
{
    /**
     * Type of method that will be used to build a CSS
     */
    const CSS = 'styles';

    /**
     * Type of method that will be used to build a JS
     */
    const JS = 'scripts';

    /**
     * Output file
     */
    const WEBPACK_FILE = 'webpack.mix.js';

    /**
     * Static mix files that will be included in the created webpack
     */
    const WEBPACK_STATIC_FILE = 'webpack.mix.static.js';

    /**
     * Use to store cache assets for "matching assets" purpose
     *
     * @var array
     */
    private $cacheFiles = [];

    /**
     * Returns the assets whether build or not
     *
     * @param bool $returnBundle
     * @return array
     */
    public function getAssets($returnBundle = true)
    {
        /**
         * Process the module assets
         */
        $resources = $this->config()->getItem('meliscore/ressources');

        $useBuildAssets = (bool) $resources['build']['use_build_assets'];

        $cssBuild = $resources['build']['css'];
        $jsBuild = $resources['build']['js'];
        $webpack = file_get_contents($this->getWebPackMixFile());
        $webpackStatic = '';

        if (file_exists($this->getWebPackMixStaticFile())) {
            $webpackStatic = file_get_contents($this->getWebPackMixStaticFile());
        }

        $webpack .= $webpackStatic;

        $scripts = $this->getMergedAssets($useBuildAssets)['js'];
        $stylesheets = $this->getMergedAssets($useBuildAssets)['css'];

        $assets = [
            'css' => $stylesheets,
            'js' => $scripts,
        ];

        $nonBundledCss = $this->getMergedAssets()['css'];
        $nonBundledJs = $this->getMergedAssets()['js'];


        if ($useBuildAssets) {

            $nonBundledJs = array_map(function ($a) {
                // remove the URI query
                $a = str_replace('/', '\/', $a);

                return preg_replace('/\?(.+?)*/', '', $a);
            }, $nonBundledJs);

            $nonBundledCss = array_map(function ($a) {
                // remove the URI query
                $a = str_replace('/', '\/', $a);

                return preg_replace('/\?(.+?)*/', '', $a);
            }, $nonBundledCss);


            // check each file if it has been included in the build assets, if not then stack it
            // to the the assets
            foreach ($nonBundledJs as $key => $script) {
                if (!preg_match("/$script/", $webpack)) {
                    $jsBuild[$script] = $script;
                }
            }

            foreach ($nonBundledCss as $style) {
                if (!preg_match("/$style/", $webpack)) {
                    $cssBuild[$style] = $style;
                }
            }

            $cssBuild = array_map(function ($a) {
                return str_replace('\/', '/', $a);
            }, $cssBuild);

            $jsBuild = array_map(function ($a) {
                return str_replace('\/', '/', $a);
            }, $jsBuild);

        }

        /**
         * check if we return the bundle all or the default one
         */
        if($returnBundle) {
            $platformTable = $this->getServiceManager()->get('MelisCoreTablePlatform');
            $platformData = $platformTable->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
            $time = '';
            if(!empty($platformData))
                $time = '?v='.$platformData->plf_bundle_cache_time;

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/'.ModulesController::BUNDLE_FOLDER_NAME.'/css/bundle-all.css')) {
                $assets['css'] = [];
                $assets['css'][] = '/'.ModulesController::BUNDLE_FOLDER_NAME.'/css/bundle-all.css'.$time;
            }

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/'.ModulesController::BUNDLE_FOLDER_NAME.'/js/bundle-all.js')) {
                $assets['js'] = [];
                $assets['js'][] = '/'.ModulesController::BUNDLE_FOLDER_NAME.'/js/bundle-all.js'.$time;
            }
        }

        return $assets;
    }


    /**
     * @return \MelisAssetManager\Service\MelisConfigService
     */
    private function config()
    {
        return $this->getServiceManager()->get('MelisConfig');
    }

    /**
     * Returns the path of "webpack.mix.js"
     *
     * @return string
     */
    protected function getWebPackMixFile()
    {
        $webPackPath = $_SERVER['DOCUMENT_ROOT'];
        $file = self::WEBPACK_FILE;
        $webpack = $webPackPath .DIRECTORY_SEPARATOR.'..'. DIRECTORY_SEPARATOR . $file;

        return $webpack;
    }

    /**
     * Returns the path of "webpack.mix.static.js"
     *
     * @return string
     */
    public function getWebPackMixStaticFile()
    {
        $webPackPath = $_SERVER['DOCUMENT_ROOT'];
        $file = self::WEBPACK_STATIC_FILE;
        $webpack = $webPackPath .DIRECTORY_SEPARATOR.'..'. DIRECTORY_SEPARATOR . $file;

        return $webpack;
    }

    /**
     * Returns assets from the loaded modules
     *
     * @param bool $useBundle
     *
     * @return array
     */
    public function getMergedAssets($useBundle = false)
    {
        $plugin = new MelisHeadPluginHelper($this->getServiceManager());
        $assets = $plugin->__invoke('/', $useBundle);

        return $assets;
    }

    /**
     * Checks all modules and compile all assets from each modules that will
     * be used in webpack
     *
     * @return string
     */
    public function buildWebPack()
    {
        $assets = $this->getMergedAssets();
        $modules = [];
        $moduleAssets = [];

        $buildPath = $this->config()->getItem('meliscore/ressources');
        $buildPath = $buildPath['build']['build_path'];
        $ds = '/';


        // get the module details via their assets info
        foreach ($assets['css'] as $idx => $asset) {

            $this->setCachedFile($asset);

            $fileFragments = explode($ds, $asset);
            $module = $fileFragments[1] ?? null;

            if ($module) {

                $modulePath = $this->module()->getModulePath($module);

                if ($modulePath) {

                    // check if the build directory for CSS exists
                    $cssBuildPath = $modulePath . $ds . $buildPath . $ds . 'css';
                    if (!file_exists($cssBuildPath)) {
                        @mkdir($cssBuildPath, 0777, true);
                    }

                    // make sure that the file has the right access
                    @chmod($cssBuildPath, 0777);

                    $modules[$module]['path'] = $modulePath;
                    if (preg_match("/$module/", $asset)) {
                        $modules[$module]['css'][] = preg_replace("/\/$module\//", $modulePath . $ds . 'public' . $ds, $asset, 1);
                    }
                }
            }
        }

        foreach ($assets['js'] as $idx => $asset) {

            $this->setCachedFile($asset);

            $fileFragments = explode('/', $asset);
            $module = $fileFragments[1] ?? null;

            if ($module) {

                $modulePath = $this->module()->getModulePath($module);

                if ($modulePath) {
                    // check if the build directory for JS exists
                    $jsBuildPath = $modulePath . $ds . $buildPath . $ds . 'js';
                    if (!file_exists($jsBuildPath)) {
                        @mkdir($jsBuildPath, 0777, true);
                    }

                    // make sure that the file has the right access
                    @chmod($jsBuildPath, 0777);

                    $modules[$module]['path'] = $modulePath;
                    if (preg_match("/$module/", $asset)) {
                        $modules[$module]['js'][] = preg_replace("/\/$module\//", $modulePath . $ds . 'public' . $ds, $asset, 1);
                    }
                }
            }
        }

        $mixScript = $this->mixFilePrefix();


        foreach ($modules as $key => $module) {
            $hasCss = $module['css'] ?? false;
            $hasJs = $module['js'] ?? false;

            $mixScript .= '// ' . $key . PHP_EOL;
            $path = $module['path'] . $ds . 'public' . $ds . 'build' . $ds;
            if ($hasCss) {
                $mixScript .= $this->getMixStyles($module['css'], $path . 'css' . $ds . 'bundle.css');
            }

            if ($hasJs) {
                $mixScript .= $this->getMixScripts($module['js'], $path . 'js' . $ds . 'bundle.js');
            }

        }

        $webPackPath = $_SERVER['DOCUMENT_ROOT'] . $ds;
        $mixScript .= $this->getCached();
        $webpack = $this->getWebPackMixFile();

        file_put_contents($webpack, $mixScript);
        @chmod($webpack, 0777);

        $runWebPackPath = str_replace('public/', '', $webPackPath);

        return "Webpack has been generated in $webpack\r\nRun \"npm run development\" or \"npm run production\" (if you want your assets to be minified) in $runWebPackPath to generate your bundled assets.";

    }

    /**
     * Stores the files where it will be used to generate a compiled asset
     *
     * @param $file
     */
    public function setCachedFile($file)
    {
        $this->cacheFiles = array_merge($this->getCachedFiles(), [$file]);
    }

    /**
     * Returns the cached files
     *
     * @return array
     */
    public function getCachedFiles()
    {
        return $this->cacheFiles;
    }

    /**
     * @return \MelisAssetManager\Service\MelisModulesService
     */
    private function module()
    {
        return $this->getServiceManager()->get('MelisAssetManagerModulesService');
    }

    private function mixFilePrefix()
    {
        $staticFile = self::WEBPACK_STATIC_FILE;

        return "require('./$staticFile');\r\n" .
            "let mix = require('laravel-mix');\r\n\r\n";
    }

    /**
     * @param $css
     * @param $path
     *
     * @return string
     */
    private function getMixStyles($css, $path)
    {
        return $this->createMixMethod(self::CSS, $css, $path);
    }

    /**
     * @param $type
     * @param $files
     * @param $outputPath
     *
     * @return string
     */
    private function createMixMethod($type, $files, $outputPath)
    {
        $syntax = "mix.$type([" . PHP_EOL;

        foreach ($files as $file) {

            // remove params on URL
            $file = preg_replace('/\?(.+?)*/', '', $file);
            $exists = file_exists($file) === true ? '// exists' : '// file does not exists';
            $syntax .= "\t'$file', " . PHP_EOL;
        }

        $syntax .= "], '$outputPath');\r\n\r\n";

        return $syntax;
    }

    /**
     * @param $js
     * @param $path
     *
     * @return string
     */
    private function getMixScripts($js, $path)
    {
        return $this->createMixMethod(self::JS, $js, $path);
    }

    /**
     * Used to match in the back-office to check whether the assets has already been compiled
     * or not, if not then this will be used to merge to the assets
     * @return string
     */
    private function getCached()
    {
        $syntax = "// Cached assets do not modify\r\nlet cache = [" . PHP_EOL;

        foreach ($this->getCachedFiles() as $file) {
            $syntax .= "'$file'," . PHP_EOL;
        }

        $syntax .= "];";

        return $syntax;
    }

}
