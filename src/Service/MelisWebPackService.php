<?php

namespace MelisAssetManager\Service;

use MelisCore\Service\MelisCoreGeneralService;
use MelisCore\View\Helper\MelisCoreHeadPluginHelper;
class MelisWebPackService extends MelisCoreGeneralService
{

    const CSS           = 'styles';
    const JS            = 'scripts';
    const WEBPACK_FILE  = 'webpack.mix.js';

    private $cacheFiles = [];


    /**
     * Returns the assets whether build or not
     * @return array
     */
    public function getAssets()
    {
        $assets         = [];
        $resources      = $this->config()->getItem('meliscore/ressources');
        $useBuildAssets = (bool) $resources['build']['use_build_assets'];
        $cssBuild       = $resources['build']['css'];
        $jsBuild        = $resources['build']['js'];
        $webpack        = file_get_contents($this->getWebPackMixFile());


        $scripts        = $this->getMergedAssets()['js'];
        $stylesheets    = $this->getMergedAssets()['css'];

        $assets = [
            'css' => $stylesheets,
            'js'  => $scripts
        ];

        if(true === $useBuildAssets) {

            $scripts    = array_map(function($a) {
                // remove the URI query
                $a = str_replace('/', '\/', $a);
                return preg_replace('/\?(.+?)*/', '', $a);
            }, $scripts);

            $stylesheets = array_map(function($a) {
                // remove the URI query
                $a = str_replace('/', '\/', $a);
                return preg_replace('/\?(.+?)*/', '', $a);
            }, $stylesheets);


            // loading of translations are not being recognized by the webpack, we have to
            // add it manually
            $jsBuild[] = '/melis/MelisCore/Language/getTranslations';

            // check each file if it has been included in the build assets, if not then stack it
            // to the the assets
            foreach($scripts as $script) {
                if(!preg_match("/$script/", $webpack)) {
                    $jsBuild[] = $script;
                }
            }

            foreach($stylesheets as $style) {
                if(!preg_match("/$style/", $webpack)) {
                    $cssBuild[] = $style;
                }
            }

            $cssBuild = array_map(function($a) {
                return str_replace('\/', '/', $a);
            }, $cssBuild);

            $jsBuild = array_map(function($a) {
                return str_replace('\/', '/', $a);
            }, $jsBuild);

            $assets = [
                'css' => $cssBuild,
                'js'  => $jsBuild
            ];

        }

        return $assets;
    }

    /**
     * This action generates a webpack.mix.js file that will be used build a compiled
     * CSS and JS
     * @return string
     */
    public function buildWebPack()
    {
        // check if MelisCore is loaded
        $mm      = $this->getServiceLocator()->get('ModuleManager');
        $modules = array_keys($mm->getLoadedModules());
        $core    = 'MelisCore';

        if(!in_array($core, $modules)) {
            return;
        }

        $webPackPath = str_replace('public', '', $_SERVER['DOCUMENT_ROOT'] );
        $webpack     = $this->getWebPackMixFile();

        if(file_exists($webpack)) {
            // webpack should readable, writable, and executable
            chmod($webpack, 0777);
        }

        $cssBuildPath = $this->config()->getItem('meliscore/ressources/build')['css_build_path'];
        $jsBuildPath  = $this->config()->getItem('meliscore/ressources/build')['js_build_path'];
        $resources    = $this->getMergedAssets();

        if(!file_exists($cssBuildPath))
            mkdir($cssBuildPath, 0777, true);

        if(!file_exists($jsBuildPath))
            mkdir($jsBuildPath, 0777, true);

        $stylesheets = $resources['css'];
        $scripts     = $resources['js'];


        $webpackSyntax =  "let mix = require('laravel-mix');" . PHP_EOL . PHP_EOL .
            $this->getMixStyles($stylesheets, $cssBuildPath) . PHP_EOL . PHP_EOL .
            $this->getMixScripts($scripts, $jsBuildPath) . PHP_EOL;

        $webpackSyntax .= PHP_EOL . $this->includeCache($this->getCachedFiles());


        file_put_contents($webpack, $webpackSyntax);
        chmod($webpack, 0777);


        return "Webpack has been generated in $webpack\r\nRun \"npm run development\" or \"npm run production\" (if you want your assets to be minified) in $webPackPath to generate your files.";

    }

    /**
     * Generate a mix syntax for CSS
     * @param $css
     * @param $path
     * @return string
     */
    public function getMixStyles($css, $path)
    {
        return $this->buildMix(self::CSS, $css, $path.'build.css');
    }

    /**
     * Generate a mix syntax for JS
     * @param $js
     * @param $path
     * @return string
     */
    public function getMixScripts($js, $path)
    {
        return $this->buildMix(self::JS, $js, $path.'build.js');
    }

    /**
     * Used to match in the back-office to check whether the assets has already been compiled
     * or not, if not then this will be used to merge to the assets
     * @param $files
     * @return string
     */
    public function includeCache($files)
    {
        $syntax = "let cache = [" . PHP_EOL;

        foreach($files as $file) {
            $syntax .= "'$file'," . PHP_EOL;
        }

        $syntax .= "];";

        return $syntax;
    }


    /**
     * Script helper to write a syntax what will be used
     * by the webpack to generate a compiled CSS or JS
     * @param $type
     * @param $files
     * @param $path
     * @return string
     */
    private function buildMix($type, $files, $path)
    {
        $syntax  = "mix.$type([" . PHP_EOL;

        foreach($files as $file) {

            // remove params on URL
            $file = preg_replace('/\?(.+?)*/', '', $file);

            $this->setCachedFile($file);

            // get the prefix of the assets and look for its' path
            $modulePath = '';
            $exists     = false;

            $fileFragments = explode('/', $file);
            // we assume that the index "1" is the module
            $module = $fileFragments[1] ?? null;
            if($module) {
                $modulePath = $this->module()->getModulePath($module, true);

                if($modulePath) {
                    $modulePath = str_replace('public/../', '', $modulePath);

                    // restructure file path
                    $fileFragments = array_splice($fileFragments, 2);
                    $file = $modulePath . '/public/' .implode('/', $fileFragments);

                    if(file_exists($file)) {
                        $exists = true;
                    }
                }
            }

            if(!$exists)
                $syntax .= "\t//'$file', <-- file does not exists". PHP_EOL;
            else
                $syntax .= "\t'$file', ". PHP_EOL;
        }

        $syntax .=  "], '$path');";

        return $syntax;
    }



    /**
     * @return \MelisAssetManager\Service\MelisModulesService
     */
    private function module()
    {
        return $this->getServiceLocator()->get('MelisAssetManagerModulesService');
    }

    /**
     * @return \MelisCore\Service\MelisCoreConfigService
     */
    private function config()
    {
        return $this->getServiceLocator()->get('MelisCoreConfig');
    }

    /**
     * Returns the path of "webpack.mix.js"
     * @return string
     */
    protected function getWebPackMixFile()
    {
        $webPackPath = str_replace('public', '', $_SERVER['DOCUMENT_ROOT'] );
        $file        = self::WEBPACK_FILE;
        $webpack     = $webPackPath.$file;

        return $webpack;
    }

    /**
     * Stores the files where it will be used to generate a compiled asset
     * @param $file
     */
    public function setCachedFile($file)
    {
        $this->cacheFiles  = array_merge($this->getCachedFiles(), [$file]);
    }

    /**
     * Returns the cached files
     * @return array
     */
    public function getCachedFiles()
    {
        return $this->cacheFiles;
    }

    /**
     * Returns assets from the loaded modules
     * @return array
     */
    public function getMergedAssets()
    {
        $plugin = new MelisCoreHeadPluginHelper($this->getServiceLocator());
        $assets = $plugin->__invoke();

        return $assets;
    }



}