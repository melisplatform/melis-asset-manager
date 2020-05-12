<?php

$isCliReqs = php_sapi_name() == 'cli' ? true : false;

// Composer cache directory and file name
$cachePackagesFiles = 'cache/composer_packages/melis_packages.dat';

//third party file
$cacheFile = !$isCliReqs ? $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$cachePackagesFiles : $cachePackagesFiles;

if (file_exists($cacheFile)) {
    /**
     * Deleting packages cache file when composer creates an update, require ...
     */
    unlink($cachePackagesFiles);
    return true;
}else{
    return [
        'success' => true,
        'message' => 'Melis cache packages files deleted'
    ];
}