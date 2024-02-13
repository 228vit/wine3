<?php


namespace App\Service;


use App\Entity\WineColor;
use App\Repository\WineColorRepository;

final class WineColorService
{
    private $wineColorRepository;
    private $wineColors = [];

    public function __construct(WineColorRepository $wineColorRepository)
    {
        $this->wineColorRepository = $wineColorRepository;
        $this->wineColors = $this->getWineColors();
    }

    public function getWineColor(string $color): ?WineColor
    {
        $color = mb_strtolower($color);
        // TODO: if not set, save as WineColorAlias
        return isset($this->wineColors[$color]) ? $this->wineColors[$color] : null;
    }

    public function getWineColors(): array
    {
        $wineColorDb = $this->wineColorRepository->getAllJoined();
        $wineColors = [];

        /** @var WineColor $wineColor */
        foreach ($wineColorDb as $wineColor) {
            $ws = mb_strtolower($wineColor->getName());

            $wineColors[$ws] = $wineColor;

            foreach ($wineColor->getAliases() as $sugarAlias) {
                $sa = mb_strtolower($sugarAlias->getName());
                $wineColors[$sa] = $wineColor;
            }
        }

        return $wineColors;
    }

}