<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

return array(
    'router' => array(
        'routes' => array(
            'melis-backoffice' => [
                'child_routes' => [
                    'webpack_builder' => [
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'build-webpack',
                            'defaults' => array(
                                'controller' => 'MelisAssetManager\Controller\WebPack',
                                'action' => 'buildWebpack',
                            ),
                        ),
                    ]
                ]
            ]

        ),
    ),
    'translator' => array(
    	'locale' => 'en_EN',
	),
    'service_manager' => array(
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
        'factories' => array(
            'MelisAssetManagerModulesService' => MelisAssetManager\Service\Factory\MelisModulesServiceFactory::class,
            'MelisAssetManagerWebPack' => MelisAssetManager\Service\Factory\MelisWebPackServiceFactory::class,
            'MelisConfig' => MelisAssetManager\Service\Factory\MelisConfigServiceFactory::class,
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'MelisAssetManager\Controller\WebPack' => 'MelisAssetManager\Controller\WebPackController'
        ),
    ),
    'view_manager' => array(
    ),
);
