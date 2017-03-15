<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */
 
namespace MelisAssetManager;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleEvent;
use Zend\Stdlib\ArrayUtils;

 
class Module
{
    private $modulePathFile = '/melis.modules.path.php';
    private $mimePathFile = '/../config/mime.config.php';
    
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
    
    public function init(ModuleManager $manager)
    {
        $eventManager = $manager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'onLoadModulesPost']);

        $this->displayFile();
    }
    
    public function onLoadModulesPost(ModuleEvent $event)
    {
        $sm = $event->getParam('ServiceManager');
        
        $assetConfigFolder = $_SERVER['DOCUMENT_ROOT'] . '/../config'; 
        $sitesModulesFolder = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        
        $modulePathFile = $assetConfigFolder . $this->modulePathFile;
        if (!file_exists($modulePathFile))
        {
            $modulesService = $sm->get('MelisAssetManagerModulesService');
            
            $modulesList = array();
            
            $allModules = $modulesService->getAllModules();
            $sitesModules = $modulesService->getSitesModules();
            
            // BO Activated Modules
            foreach ($allModules as $moduleName)
            {
                $path = $modulesService->getModulePath($moduleName);
                $modulesList[$moduleName] = $path;
            }
            
            // Sites modules
            foreach ($sitesModules as $moduleName)
            {
                $path = $sitesModulesFolder . '/' . $moduleName;
                $modulesList[$moduleName] = $path;
            }
            
            $fd = fopen($modulePathFile, 'w');
            if ($fd)
            {
                $modulesPathsArray = "<?php \n\n";
                $modulesPathsArray .= "\treturn array( \n";
    
                $pathFile = '';
                foreach ($modulesList as $moduleName => $modulePath)
                {
                    $modulesPathsArray .= "\t\t'$moduleName' => '$modulePath', \n";
                }
                $modulesPathsArray .= "\t); \n";
                
                fwrite($fd, $modulesPathsArray);
                fclose($fd);
                chmod($modulePathFile, 0777);
                
                $this->displayFile();
            }
        }
        
    }
    
    public function displayFile()
    {
        $assetConfigFolder = $_SERVER['DOCUMENT_ROOT'] . '/../config';
        $uri = $_SERVER['REQUEST_URI'];
        
        $UriWithoutParams = explode('?', $uri);
        $UriWithoutParams = $UriWithoutParams[0];
        
        // First check if asset in main public folder
        $pathFile = $_SERVER['DOCUMENT_ROOT'] . $UriWithoutParams;
        if (is_file($pathFile))
            $this->sendDocument($pathFile);
        else
        {
            // testing module public folder second
            if (file_exists($assetConfigFolder . $this->modulePathFile))
            {
                $loadedModules = \MelisCore\MelisModuleManager::getModules();
                $modulesPath = require $assetConfigFolder . $this->modulePathFile;
                
                $detailUri = explode('/', $UriWithoutParams);
                if (count($detailUri) > 1)
                {
                    $moduleUri = $detailUri[1];
                    
                    // Need to have a path defined, and module loaded 
                    if (!empty($modulesPath[$moduleUri]) && in_array($moduleUri, $loadedModules))
                    {
                        $path = $modulesPath[$moduleUri];
    
                        $pathFile = $path . '/public';
                        for ($i = 2; $i < count($detailUri); $i++)
                            $pathFile .= '/' . $detailUri[$i];
    
                        if ($pathFile != '')
                        {
                            if (is_file($pathFile))
                                $this->sendDocument($pathFile);
                        }
                    }
                }
            }
        }
    }
    
    public function getMimeType($filename)
    {
        $mimeConfig = require __DIR__ . $this->mimePathFile;
                            
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
        if (isset($mimeConfig['mime'][$extension])) {
            return $mimeConfig['mime'][$extension];
        }
    
        return 'text/plain';
    }
    
    public function sendDocument($pathFile)
    {
        $mime = $this->getMimeType($pathFile);
        
        header('HTTP/1.0 200 OK');
        header("Content-Type: " . $mime);
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Accept-Ranges: bytes');
        header("Content-Transfer-Encoding: binary\n");
        header('Connection: close');
        
        readfile($pathFile);
        
        die;
    }
    
    public function getConfig()
    {
    	$config = array();
    	$configFiles = array(
    			include __DIR__ . '/../config/module.config.php',
    	);
    	
    	foreach ($configFiles as $file) {
    		$config = ArrayUtils::merge($config, $file);
    	} 
    	
    	return $config;
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
 
}
