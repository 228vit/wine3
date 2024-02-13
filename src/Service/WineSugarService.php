<?php


namespace App\Service;


use App\Entity\WineSugar;
use App\Repository\WineSugarRepository;

final class WineSugarService
{
    private $wineSugarRepository;
    private $wineSugars = [];

    public function __construct(WineSugarRepository $wineSugarRepository)
    {
        $this->wineSugarRepository = $wineSugarRepository;
        $this->wineSugars = $this->getWineSugars();
    }

    public function getWineSugar(string $sugar): ?WineSugar
    {
        $sugar = mb_strtolower($sugar);
        // TODO: if not set, save as WineSugarAlias
        return isset($this->wineSugars[$sugar]) ? $this->wineSugars[$sugar] : null;
    }

    public function getWineSugars(): array
    {
        $wineSugarDb = $this->wineSugarRepository->getJoinedAliases();
        $wineSugars = [];

        /** @var WineSugar $wineSugar */
        foreach ($wineSugarDb as $wineSugar) {
            $ws = mb_strtolower($wineSugar->getName());

            $wineSugars[$ws] = $wineSugar;

            foreach ($wineSugar->getAliases() as $sugarAlias) {
                $sa = mb_strtolower($sugarAlias->getName());
                $wineSugars[$sa] = $wineSugar;
            }
        }

        return $wineSugars;
    }

}