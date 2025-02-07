<?php

namespace MelisAssetManager\View\Helper;

use Laminas\View\Helper\AbstractHelper;

abstract class AbstractMelisIconsHelper extends AbstractHelper
{
    /**
     * @param string $bgColorFill
     * @param string $lightColorFill
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public function createIcon($bgColorFill = '#ee6622', $lightColorFill = '#ffffff', $width = 30, $height = 30)
    {
        $icon = <<<DOM
            <svg class="melis-icon" style="width:{$width}px;height:{$height}px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
                <rect fill="$bgColorFill" x=".07" y=".13" width="79.86" height="79.86" rx="15.36" ry="15.36"/>
                <g>
                    <path fill="$lightColorFill" d="M57.78,15.87c-3.47,0-6.29,2.81-6.29,6.29v35.85c0,3.47,2.81,6.29,6.29,6.29s6.29-2.81,6.29-6.29V22.16c0-3.47-2.81-6.29-6.29-6.29Z"/>
                    <path fill="$lightColorFill" d="M27.79,19.16c-1.62-3.07-5.43-4.24-8.5-2.62-3.07,1.62-4.24,5.43-2.62,8.5l19.01,35.93c1.62,3.07,5.43,4.24,8.5,2.62,3.07-1.62,4.24-5.43,2.62-8.5L27.79,19.16Z"/>
                    <circle fill="$lightColorFill" cx="22.36" cy="57.88" r="6.43"/>
                </g>
            </svg>
DOM;
        return  $icon;
    }
}
