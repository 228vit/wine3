<?php

namespace App\Entity;

use App\Repository\WineColorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WineColorRepository::class)
 */
class WineColor
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
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="wineColor")
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity=WineColorAlias::class, mappedBy="wineColor", orphanRemoval=true, cascade={"persist"})
     */
    private $aliases;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $engName;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setWineColor($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getWineColor() === $this) {
                $product->setWineColor(null);
            }
        }

        return $this;
    }

    public function __toString(): ?string
    {
        return $this->getName();
    }

    /**
     * @return Collection|WineColorAlias[]
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    public function addAlias(WineColorAlias $alias): self
    {
        if (!$this->aliases->contains($alias)) {
            $this->aliases[] = $alias;
            $alias->setWineColor($this);
        }

        return $this;
    }

    public function removeAlias(WineColorAlias $alias): self
    {
        if ($this->aliases->removeElement($alias)) {
            // set the owning side to null (unless already changed)
            if ($alias->getWineColor() === $this) {
                $alias->setWineColor(null);
            }
        }

        return $this;
    }

    public function getAliasesAsString($delimiter = ', '): string
    {
        $aliases = [];
        foreach ($this->getAliases() as $alias) {
            $aliases[] = $alias->getName();
        }

        return implode($delimiter, $aliases);
    }

    public function getEngName(): ?string
    {
        return $this->engName;
    }

    public function setEngName(?string $engName): self
    {
        $this->engName = $engName;

        return $this;
    }
}
