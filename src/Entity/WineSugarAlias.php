<?php

namespace App\Entity;

use App\Repository\WineSugarAliasRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WineSugarAliasRepository::class)
 */
class WineSugarAlias
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=WineSugar::class, inversedBy="aliases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $wineSugar;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWineSugar(): ?WineSugar
    {
        return $this->wineSugar;
    }

    public function setWineSugar(?WineSugar $wineSugar): self
    {
        $this->wineSugar = $wineSugar;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
