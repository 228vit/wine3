<?php


namespace App\Service;


use App\Entity\WineColor;
use App\Entity\WineColorAlias;
use App\Repository\WineColorAliasRepository;
use App\Repository\WineColorRepository;

final class WineColorService
{
    private $wineColorRepository;
    private $wineColorAliasRepository;
    private $wineColors = [];

    public function __construct(WineColorRepository $wineColorRepository,
                                WineColorAliasRepository $wineColorAliasRepository)
    {
        $this->wineColorRepository = $wineColorRepository;
        $this->wineColorAliasRepository = $wineColorAliasRepository;
        $this->wineColors = $this->getWineColors();
    }

    public function getWineColor(?string $color): ?WineColor
    {
        if (null === $color) return null;

        $color = mb_strtolower($color);
        // TODO: if not set, save as WineColorAlias
        $wineColor = isset($this->wineColors[$color]) ? $this->wineColors[$color] : null;
        if (null === $color) {
            /** @var WineColorAlias $colorAlias */
            $colorAlias = $this->wineColorAliasRepository->findLikeName($color);
            if ($colorAlias) {
                $wineColor = $colorAlias->getWineColor();
            }
        }

        return $wineColor;
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