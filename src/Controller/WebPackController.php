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
        // Developer build tool: requires an authenticated back-office session and a POST.
        if (!$this->isBackOfficeUser()) {
            header('HTTP/1.0 403 Forbidden');
            die;
        }
        if (!$this->getRequest()->isPost()) {
            header('HTTP/1.1 405 Method Not Allowed');
            die;
        }
        print $this->webpack()->buildWebPack();
        die;
    }

    /**
     * Prints all the available assets
     */
    public function viewAssetsAction()
    {
        // Authenticated back-office only — this dumps the full asset inventory (module list,
        // versions).
        if (!$this->isBackOfficeUser()) {
            header('HTTP/1.0 403 Forbidden');
            die;
        }
        $assets = $this->webpack()->getAssets();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        print json_encode($assets);
        die;
    }

    /**
     * True only for an authenticated Melis back-office user.
     */
    private function isBackOfficeUser(): bool
    {
        try {
            $sm = $this->getServiceManager();
            return $sm->has('MelisCoreAuth') && (bool) $sm->get('MelisCoreAuth')->hasIdentity();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return \MelisAssetManager\Service\MelisWebPackService
     */
    private function webpack()
    {
        return $this->getServiceManager()->get('MelisAssetManagerWebPack');
    }


}