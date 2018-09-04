<?php

namespace MelisAssetManager\View\Helper;

use Zend\View\Helper\AbstractHelper;

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
    public function createIcon($bgColorFill = '#ee6622', $lightColorFill = '#f7962d', $width = 30, $height = 30)
    {
        $icon = <<<DOM
            <svg class="melis-icon" style="width:{$width}px;height:{$height}px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 30 30" enable-background="new 0 0 30 30" xml:space="preserve">
                <g>
                    <rect class="bg-color" y="0.1" fill="$bgColorFill" width="30" height="30"></rect>
                    <rect class="light-color" x="14.6" y="2.7" fill="$lightColorFill" width="12.7" height="24.6"></rect>
                    <g>
                        <path fill="#FFFFFF" d="M7.4,21.1V9.5H22v11.7h-2.9v-8.8h-2.9v8.8h-2.9v-8.8h-2.9v8.8H7.4z"></path>
                    </g>
                </g>
            </svg>
DOM;
        return  $icon;
    }
}
