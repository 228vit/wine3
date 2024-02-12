<?php

namespace App\Entity;

use App\Repository\WineSugarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WineSugarRepository::class)
 */
class WineSugar
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=WineSugarAlias::class, mappedBy="wineSugar", orphanRemoval=true, cascade={"persist"})
     */
    private $aliases;

    public function __construct()
    {
        $this->aliases = new ArrayCollection();
    }

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|WineSugarAlias[]
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    public function addAlias(WineSugarAlias $alias): self
    {
        if (!$this->aliases->contains($alias)) {
            $this->aliases[] = $alias;
            $alias->setWineSugar($this);
        }

        return $this;
    }

    public function removeAlias(WineSugarAlias $alias): self
    {
        if ($this->aliases->removeElement($alias)) {
            // set the owning side to null (unless already changed)
            if ($alias->getWineSugar() === $this) {
                $alias->setWineSugar(null);
            }
        }

        return $this;
    }

    public function getAliasesAsString($delimiter = ', '): string
    {
        $aliases = [];
        foreach ($this->getAliases() as $sugarAlias) {
            $aliases[] = $sugarAlias->getName();
        }

        return implode($delimiter, $aliases);
    }

    public function __toString(): string
    {
        return $this->getName();
    }
    
}
