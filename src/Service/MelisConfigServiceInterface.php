<?php

namespace MelisAssetManager\Service;

interface MelisConfigServiceInterface
{
    public function getItem($pathString = '');

    public function prefixIdsKeysRec($array, $prefix);
}
