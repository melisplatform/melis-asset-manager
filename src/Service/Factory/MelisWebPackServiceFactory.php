<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisAssetManager\Service\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;
use MelisAssetManager\Service\MelisWebPackService;

class MelisWebPackServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $service = new MelisWebPackService();
        $service->setServiceLocator($sl);

        return $service;
    }

}