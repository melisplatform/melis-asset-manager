<?php
namespace MelisAssetManager\Controller;

use MelisCore\Controller\MelisAbstractActionController;

class WebPackController extends MelisAbstractActionController
{
    /**
     * This action generates a webpack.mix.js file that will be used build a compiled
     * CSS and JS
     */
    public function buildWebpackAction()
    {
        // this should be generated only when in back-office
        print $this->webpack()->buildWebPack();
        die;
    }

    /**
     * Prints all the available assets
     */
    public function viewAssetsAction()
    {
        $assets = $this->webpack()->getAssets();
        print_r($assets);
        die;
    }

    /**
     * @return \MelisAssetManager\Service\MelisWebPackService
     */
    private function webpack()
    {
        return $this->getServiceManager()->get('MelisAssetManagerWebPack');
    }


}