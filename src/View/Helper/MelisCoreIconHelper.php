<?php
namespace MelisAssetManager\View\Helper;

class MelisCoreIconHelper extends AbstractMelisIconsHelper
{
    /** @var const BACKGROUND */
    const BACKGROUND = '#ee6622';

    /** @var const FOREGROUND */
    const FOREGROUND = '#f7962d';
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
