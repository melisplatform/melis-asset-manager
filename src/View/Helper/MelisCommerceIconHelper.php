<?php
namespace MelisAssetManager\View\Helper;

class MelisCommerceIconHelper extends AbstractMelisIconsHelper
{
    /** @var const BACKGROUND */
    /*
     * Orig: const BACKGROUND = '#3997d4';
     * Interchanged color hex based on melis-commerce logo https://www.melistechnology.com/
     */
    const BACKGROUND = '#2780c4';

    /** @var const FOREGROUND */
    /*
     * Orig: const FOREGROUND = '#2780c4';
     * Interchanged color hex based on melis-commerce logo https://www.melistechnology.com/
     */
    const FOREGROUND = '#ffffff';
    /**
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public function __invoke($width = 30, $height = 30)
    {
        $icon = $this->createIcon(self::BACKGROUND, self::FOREGROUND, $width, $height);
        return $icon;
    }
}
