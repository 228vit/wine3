<?php

namespace App\Entity;

use App\Repository\WineColorAliasRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WineColorAliasRepository::class)
 */
class WineColorAlias
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=WineColor::class, inversedBy="aliases", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $wineColor;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWineColor(): ?WineColor
    {
        return $this->wineColor;
    }

    public function setWineColor(?WineColor $wineColor): self
    {
        $this->wineColor = $wineColor;

        return $this;
    }
}
