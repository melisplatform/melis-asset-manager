<?php

namespace MelisAssetManager\View\Helper;

use Zend\View\Helper\AbstractHelper;
use MelisCore\Library\MelisAppConfig;

class MelisHeadPluginHelper extends AbstractHelper
{
	public $serviceManager;

	public function __construct($sm)
	{
		$this->serviceManager = $sm;
	}
	
	public function __invoke($path = '/')
	{
		$melisAppConfig = $this->serviceManager->get('MelisConfig');
		
		$appsConfig = $melisAppConfig->getItem($path);
		if ($path != '/')
	    {
	        $path = substr($path, 1, strlen($path));
	        $appsConfig = array($path => $appsConfig);
	    }
	    
		$jsFiles = array();
		$cssFiles = array();
		foreach ($appsConfig as $keyPlugin => $appConfig)
		{	
			$jsFiles = array_merge($jsFiles, $melisAppConfig->getItem("/$keyPlugin/ressources/js"));
			$cssFiles = array_merge($cssFiles, $melisAppConfig->getItem("/$keyPlugin/ressources/css"));
		}
		
		return array('js' => $jsFiles,
					 'css' => $cssFiles);
	}
}