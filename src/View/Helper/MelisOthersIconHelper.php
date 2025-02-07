<?php
namespace MelisAssetManager\View\Helper;

class MelisOthersIconHelper extends AbstractMelisIconsHelper
{
    /** @var const BACKGROUND */
    const BACKGROUND = '#ff0000'; // #C52127

    /** @var const FOREGROUND */
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
