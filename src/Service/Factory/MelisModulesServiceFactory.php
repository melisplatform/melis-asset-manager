<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisAssetManager\Service\Factory;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\FactoryInterface;
use MelisAssetManager\Service\MelisModulesService;

class MelisModulesServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{
		$modulesSvc = new MelisModulesService();
		$modulesSvc->setServiceLocator($sl);
		
		return $modulesSvc;
	}

}