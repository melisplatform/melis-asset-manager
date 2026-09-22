<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisAssetManager;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\Stdlib\ArrayUtils;

/**
 * Minify Classes
 */
$path = __DIR__ .'/../lib';

/**
 * Class Module
 * @package MelisAssetManager
 */


class Module
{
    private $modulePathFile = '/melis.modules.path.php';
    private $mimePathFile = '/../config/mime.config.php';
    
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $sm = $e->getApplication()->getServiceManager();
        $this->displayFile($sm);
    }
    
    public function init(ModuleManager $manager)
    {
        $eventManager = $manager->getEventManager();
        $eventManager->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'onLoadModulesPost']);
    }
    
    public function onLoadModulesPost(ModuleEvent $event)
    {
        $sm                 = $event->getParam('ServiceManager');
        $modulesService     = $sm->get('MelisAssetManagerModulesService');
        $assetConfigFolder  = $_SERVER['DOCUMENT_ROOT'] . '/../config';
        $sitesModulesFolder = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';
        $allModules         = $modulesService->getAllModules();

        $modulePathFile = $assetConfigFolder . $this->modulePathFile;

        $newModules = false;
        if (file_exists($modulePathFile))
        {
            // checking if there's new modules not in the path list
            $loadedModules = $modulesService->getActiveModules();
            $existingPathModules = require $assetConfigFolder . $this->modulePathFile;
            
            foreach ($loadedModules as $moduleName)
            {
                if (empty($existingPathModules[$moduleName]))
                {
                    $newModules = true;
                    break;
                }
            }
        }

        if (!file_exists($modulePathFile) || $newModules)
        {

            $modulesList  = array();
            $sitesModules = $modulesService->getSitesModules();
            
            // BO Activated Modules
            foreach ($allModules as $moduleName)
            {
                $path = $modulesService->getModulePath($moduleName);
                $path = str_replace($_SERVER['DOCUMENT_ROOT'] . '/..', '', $path);
                $modulesList[$moduleName] = $path;
            }
            
            // Sites modules
            foreach ($sitesModules as $moduleName)
            {
                $path = $sitesModulesFolder . '/' . $moduleName;
                $path = str_replace($_SERVER['DOCUMENT_ROOT'] . '/..', '', $path);
                $modulesList[$moduleName] = $path;
            }
            
            try 
            {
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
                    
                    $this->displayFile($sm);
                }
                else
                {
                    /*echo "Error generating file $modulePathFile : check rights";
                    die;*/
                }
            }
            catch (\Exception $e)
            {
                /*echo "Error generating file $modulePathFile : check rights";
                die;*/
            }
        }
    }
    
    public function displayFile($sm)
    {

        $moduleSvc =  $sm->get('MelisAssetManagerModulesService');
        $assetConfigFolder = $_SERVER['DOCUMENT_ROOT'] . '/../config';
        $uri = $_SERVER['REQUEST_URI'];
        
        $UriWithoutParams = explode('?', $uri);
        
        $UriParams = "";
        $UriWithoutParams = $UriWithoutParams[0];
        if (!empty($UriWithoutParams[1]))
            $UriParams = $UriWithoutParams[1];
        
        
        // First check if asset in main public folder
        $pathFile = $_SERVER['DOCUMENT_ROOT'] . $UriWithoutParams;
        if (is_file($pathFile) && $this->checkFileInFolder($pathFile, $_SERVER['DOCUMENT_ROOT']))
            $this->sendDocument($pathFile, $UriParams, $sm);
        else
        {
            // testing module public folder second
            if (file_exists($assetConfigFolder . $this->modulePathFile))
            {
                $loadedModules = $moduleSvc->getAllModules();
                $modulesPath = require $assetConfigFolder . $this->modulePathFile;
                
                $detailUri = explode('/', $UriWithoutParams);
                if (count($detailUri) > 1)
                {
                    $moduleUri = $detailUri[1];
                    
                    // Need to have a path defined, and module loaded 
                    if (!empty($modulesPath[$moduleUri]) && (in_array($moduleUri, $loadedModules) ||
                        strpos($modulesPath[$moduleUri], 'MelisSites') !== false))
                    {
                        $path = $modulesPath[$moduleUri];
                        
                        if (str_replace($_SERVER['DOCUMENT_ROOT'] . '/..', '', $path) == $path)
                        {
                            // relative path
                            $path = $_SERVER['DOCUMENT_ROOT'] . '/..' . $path;
                        }
    
                        $pathFile = $path . '/public';
                        for ($i = 2; $i < count($detailUri); $i++)
                            $pathFile .= '/' . $detailUri[$i];
    
                        if ($pathFile != '')
                        {
                            if (is_file($pathFile) && $this->checkFileInFolder($pathFile, $path . '/public/'))
                                $this->sendDocument($pathFile, $UriParams, $sm);
                        }
                    }
                }
            }
        }

        // SECURITY (DEKRA audit, action #20 — DoS on extension paths): reaching here means nothing
        // above was served (sendDocument() dies when it serves). If the request path denotes a FILE
        // — it carries an extension that can only mean a file, never a CMS page or an MVC route — and
        // that file exists nowhere we could serve it from, answer 404 HERE, at bootstrap, and exit.
        // Otherwise such a request fell through to the full front-page 404 render (~45 s under load in
        // the audit; the MVC listeners' own short-circuit excludes ".php"); a handful of them
        // saturated the FPM pool. Extensionless paths (CMS pages -> the normal 404) and the app's own
        // dynamically generated file-extension routes are left untouched.
        $this->declineNonExistentAssetPath($UriWithoutParams);
    }

    /**
     * 404-and-exit for a request whose path is a FILE that exists nowhere servable — called only
     * after displayFile() has failed to serve it from the webroot or any module public/ folder.
     *
     * Scoped to extensions that can ONLY mean a file (js/css/images/fonts/media/archives/php); this
     * deliberately excludes txt/xml/html, which the platform serves dynamically (robots.txt,
     * sitemap.xml, sitemap.html). The back-office prefixes (/melis/*, /melis-react/*) and MelisFront's
     * two generated stylesheets are dynamic routes that carry an extension, so they are exempted too.
     */
    protected function declineNonExistentAssetPath($uriPath)
    {
        $decoded = rawurldecode((string) $uriPath);
        $ext = strtolower(pathinfo($decoded, PATHINFO_EXTENSION));
        if ($ext === '') {
            return; // a page (or route) -> let it go to the normal 404
        }

        static $fileOnlyExt = [
            // scripts / styles / source maps
            'js' => 1, 'mjs' => 1, 'css' => 1, 'map' => 1, 'less' => 1, 'scss' => 1,
            // images
            'png' => 1, 'jpg' => 1, 'jpeg' => 1, 'gif' => 1, 'svg' => 1, 'svgz' => 1, 'ico' => 1,
            'webp' => 1, 'avif' => 1, 'bmp' => 1, 'tif' => 1, 'tiff' => 1,
            // fonts
            'woff' => 1, 'woff2' => 1, 'ttf' => 1, 'otf' => 1, 'eot' => 1,
            // media
            'mp3' => 1, 'mp4' => 1, 'webm' => 1, 'ogg' => 1, 'oga' => 1, 'ogv' => 1, 'wav' => 1,
            'flac' => 1, 'avi' => 1, 'mov' => 1, 'mkv' => 1, 'wmv' => 1,
            // documents / archives
            'pdf' => 1, 'zip' => 1, '7z' => 1, 'rar' => 1, 'gz' => 1, 'tgz' => 1, 'tar' => 1,
            'doc' => 1, 'docx' => 1, 'xls' => 1, 'xlsx' => 1, 'ppt' => 1, 'pptx' => 1, 'odt' => 1, 'ods' => 1,
            // server-side scripts — the audited case: an unmatched "/x.php" must not cost a full render
            'php' => 1, 'php3' => 1, 'php4' => 1, 'php5' => 1, 'phps' => 1, 'pht' => 1, 'phtml' => 1,
        ];
        if (!isset($fileOnlyExt[$ext])) {
            return;
        }

        // Dynamically generated routes that carry a file extension -> let the application answer.
        $first = explode('/', ltrim($decoded, '/'), 2)[0];
        if ($first === 'melis' || $first === 'melis-react') {
            return;
        }
        static $appServedPaths = ['/css/plugin-width.css' => 1, '/css/page-plugin-width.css' => 1];
        if (isset($appServedPaths[$decoded])) {
            return;
        }

        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo "Not Found\n";
        exit;
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
    
    public function sendDocument($pathFile, $UriParams, $sm = null)
    {
        $mime   = $this->getMimeType($pathFile);

        // if php file, we need to eval
        if ($mime == 'application/x-httpd-php') {
            // SECURITY: never execute a served PHP file for an anonymous request. The bundled PHP
            // helpers under module public/ (TinyMCE, file manager, moxiemanager, …) are only ever
            // used from the authenticated back-office; executing arbitrary public/*.php without a
            // session is a remote-code-execution vector. Require a valid Melis session. (Static
            // assets — the else branch below — stay public, as they must load before login.)
            if (!$this->isRequestAuthenticated($sm)) {
                header('HTTP/1.0 403 Forbidden');
                die;
            }

            header('HTTP/1.0 200 OK');
            header("Content-Type: text/html; charset=UTF-8" . $mime);

            $folderPath = explode('/', $pathFile);
            $fileName = $folderPath[count($folderPath) - 1];
            unset($folderPath[count($folderPath) - 1]);
            $folderPath = implode('/', $folderPath);

            // The file about to run (MoxieManager's api.php, the TinyMCE helpers...) executes
            // outside the MVC stack and has no way to reach the platform's services. Exposing
            // the service manager lets it write into the audit trail - see the MelisAudit
            // plugin of MoxieManager (DEKRA item 21.0).
            $GLOBALS['melisServiceManager'] = $sm;

            eval ( ' chdir("' . $folderPath . '"); require "' . $fileName . '";' );
        } else {

            $content = file_get_contents($pathFile);


            header('HTTP/1.0 200 OK');
            header("Content-Type: " . $mime);

            $seconds_to_cache = 60 * 60 * 24; // 24hrs
            $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
            header("Expires: $ts");
            header("Pragma: cache");
            header("Cache-Control: max-age=$seconds_to_cache");

            print $content;
        }

        die;
    }
    
    public function getConfig()
    {
    	$config = [];
    	$configFiles = [
    			include __DIR__ . '/../config/module.config.php',
    	];

    	foreach ($configFiles as $file) {
    		$config = ArrayUtils::merge($config, $file);
    	}

    	return $config;
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * SECURITY guard for PHP execution via the asset handler: true only when the current request
     * carries a valid authenticated Melis session. The session is started here if needed (this
     * handler runs early in bootstrap and dies before MelisCore's initSession); we only READ the
     * existing session cookie, and only for the .php branch, so static-asset serving is untouched.
     *
     * MelisCoreAuth is NOT always available here: MelisModuleManager::getModules() only boots the
     * back-office module set when the first URI segment is 'melis' or a BO module name. An asset
     * URL of a SITE module (e.g. /MelisDemoCms/miniTemplatesTinyMce/x.phtml, requested by the
     * TinyMCE mini-template picker) boots a stripped module set WITHOUT MelisCore, so the service
     * is undefined and asking for it threw -> every mini-template 403'd for logged-in users too.
     * Hence the session fallback below, which reads exactly what hasIdentity() reads: the
     * 'Melis_Auth' container written by Laminas\Authentication\Storage\Session (see
     * MelisCoreAuthService::setServiceManager()).
     */
    protected function isRequestAuthenticated($sm): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // No session cookie at all -> anonymous for sure, and don't create an empty session.
            if (empty($_COOKIE[session_name()])) {
                return false;
            }
            @session_start();
        }

        // Source of truth when MelisCore is part of the booted module set.
        if ($sm !== null) {
            try {
                if ($sm->has('MelisCoreAuth')) {
                    return (bool) $sm->get('MelisCoreAuth')->hasIdentity();
                }
            } catch (\Throwable $e) {
                // fall through to the session probe
            }
        }

        $authNamespace = $_SESSION['Melis_Auth'] ?? null;
        if (is_array($authNamespace)) {
            return !empty($authNamespace['storage']);
        }
        if ($authNamespace instanceof \ArrayAccess) {
            return isset($authNamespace['storage']) && !empty($authNamespace['storage']);
        }

        return false;
    }

     /*checks if the file is inside the given folder*/
    protected function checkFileInFolder($file, $folder)
    {       
        $path = realpath($file);        
        if ($path !== false) {
            if (strpos($path, realpath($folder)) !== 0) {                   
                throw new \Exception('Requested resource is outside of ' . $folder);
            } else {              
                return true;
            }
        } else {                   
            return false;
        }
    }
}
