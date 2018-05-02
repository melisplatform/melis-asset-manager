<?php

namespace MelisAssetManager\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;
use MelisCore\Library\MelisAppConfig;

class MelisHeadPluginHelper extends AbstractHelper
{

    /**
     * @var ServiceLocatorAwareInterface
     */
	public $serviceManager;

    /**
     * MelisHeadPluginHelper constructor.
     * @param $sm
     */
	public function __construct($sm)
	{
		$this->serviceManager = $sm;
	}

    /**
     * @note Add 'disable_bundle' => true configuration inside "ressources/build" if you don't want to use bundled assets on a specific module
     * @param string $path
     * @param bool $useBundle
     * @return array
     */
	public function __invoke($path = '/', $useBundle = false)
	{
		$melisAppConfig = $this->serviceManager->get('MelisConfig');
		
		$appsConfig = $melisAppConfig->getItem($path);
		if ($path != '/')
	    {
	        $path = substr($path, 1, strlen($path));
	        $appsConfig = array($path => $appsConfig);
	    }
	    
		$jsFiles      = array();
		$cssFiles     = array();

		foreach ($appsConfig as $keyPlugin => $appConfig)
		{
            $resourcePath = true === $useBundle ? 'ressources/build' : 'ressources';

		    if(!in_array($keyPlugin, $this->pathExceptions())) {


                // check for bundle usage overrides
                $disableBundle = false;


                if($resourcePath == 'ressources/build') {
                    $disableBundle = isset($melisAppConfig->getItem("/$keyPlugin/$resourcePath")['disable_bundle']) ?
                        (bool) $melisAppConfig->getItem("/$keyPlugin/$resourcePath")['disable_bundle'] : false;

                    if($disableBundle) {
                        $resourcePath = 'ressources';
                    }
                }

                $jsFiles  = array_merge($jsFiles, $melisAppConfig->getItem("/$keyPlugin/$resourcePath/js"));
                $cssFiles = array_merge($cssFiles, $melisAppConfig->getItem("/$keyPlugin/$resourcePath/css"));
            }

		}
		
		return array('js'  => $jsFiles,
					 'css' => $cssFiles);
	}

    /**
     * @return array
     */
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