<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

return [
    'router' => [
        'routes' => [
            'melis-backoffice' => [
                'child_routes' => [
                    'webpack_builder' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => 'build-webpack',
                            'defaults' => [
                                'controller' => 'MelisAssetManager\Controller\WebPack',
                                'action' => 'buildWebpack',
                            ],
                        ],
                    ],
                    'view_assets' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => 'view-assets',
                            'defaults' => [
                                'controller' => 'MelisAssetManager\Controller\WebPack',
                                'action' => 'viewAssets',
                            ],
                        ],
                    ],
                ],
            ],

        ],
    ],
    'translator' => [
        'locale' => 'en_EN',
    ],
    'service_manager' => [
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
        'factories' => [
            'MelisAssetManagerModulesService' => MelisAssetManager\Service\Factory\MelisModulesServiceFactory::class,
            'MelisAssetManagerWebPack' => MelisAssetManager\Service\Factory\MelisWebPackServiceFactory::class,
            'MelisConfig' => MelisAssetManager\Service\Factory\MelisConfigServiceFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'MelisAssetManager\Controller\WebPack' => 'MelisAssetManager\Controller\WebPackController',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'melisCoreIcon' => \MelisAssetManager\View\Helper\MelisCoreIconHelper::class,
            'melisCmsIcon' => \MelisAssetManager\View\Helper\MelisCmsIconHelper::class,
            'melisMarketingIcon' => \MelisAssetManager\View\Helper\MelisMarketingIconHelper::class,
            'melisCommerceIcon' => \MelisAssetManager\View\Helper\MelisCommerceIconHelper::class,
            'melisOthersIcon' => \MelisAssetManager\View\Helper\MelisOthersIconHelper::class,
            'melisCustomIcon' => \MelisAssetManager\View\Helper\MelisCustomIconHelper::class,
        ],
    ],
    'view_manager' => [
    ],
];
