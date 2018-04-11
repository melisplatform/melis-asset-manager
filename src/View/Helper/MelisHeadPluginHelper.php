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
		    if(!in_array($keyPlugin, $this->pathExceptions())) {
                $jsFiles = array_merge($jsFiles, $melisAppConfig->getItem("/$keyPlugin/ressources/js"));
                $cssFiles = array_merge($cssFiles, $melisAppConfig->getItem("/$keyPlugin/ressources/css"));
            }

		}
		
		return array('js'  => $jsFiles,
					 'css' => $cssFiles);
	}

	protected function pathExceptions()
    {
        return [
            'meliscore_login',
            'meliscore_lost_password',
            'meliscore_reset_password',
            'melis_core_setup',
            'melis_engine_setup'
        ];
    }
}