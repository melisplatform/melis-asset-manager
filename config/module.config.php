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
                    ],
                    'view_assets' => [
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'view-assets',
                            'defaults' => array(
                                'controller' => 'MelisAssetManager\Controller\WebPack',
                                'action' => 'viewAssets',
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
    'view_helpers' => [
        'invokables' => [
            'melisCoreIcon' => \MelisAssetManager\View\Helper\MelisCoreIconHelper::class,
            'melisCmsIcon' => \MelisAssetManager\View\Helper\MelisCmsIconHelper::class,
            'melisMarketingIcon' => \MelisAssetManager\View\Helper\MelisMarketingIconHelper::class,
            'melisCommerceIcon' => \MelisAssetManager\View\Helper\MelisCommerceIconHelper::class,
            'melisOthersIcon' => \MelisAssetManager\View\Helper\MelisOthersIconHelper::class,
            'melisCustomIcon' => \MelisAssetManager\View\Helper\MelisCustomIconHelper::class,
        ]
    ],
    'view_manager' => array(
    ),
);
