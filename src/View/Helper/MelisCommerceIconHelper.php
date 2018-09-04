<?php
namespace MelisAssetManager\View\Helper;

class MelisCommerceIconHelper extends AbstractMelisIconsHelper
{
    /** @var const BACKGROUND */
    const BACKGROUND = '#3997d4';

    /** @var const FOREGROUND */
    const FOREGROUND = '#2780c4';
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
