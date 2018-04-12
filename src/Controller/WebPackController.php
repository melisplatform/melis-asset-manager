<?php
namespace MelisAssetManager\Controller;

use Zend\Mvc\Controller\AbstractActionController;
class WebPackController extends AbstractActionController
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
     * @return \MelisAssetManager\Service\MelisWebPackService
     */
    private function webpack()
    {
        return $this->getServiceLocator()->get('MelisAssetManagerWebPack');
    }


}