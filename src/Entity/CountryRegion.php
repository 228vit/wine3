<?php

namespace App\Entity;

use App\Repository\CountryRegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CountryRegionRepository::class)
 */
class CountryRegion
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
     * @ORM\ManyToOne(targetEntity=Country::class, inversedBy="regions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $country;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="region")
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity=Appellation::class, mappedBy="countryRegion")
     */
    private $appellations;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->appellations = new ArrayCollection();
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

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
            $product->setRegion($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getRegion() === $this) {
                $product->setRegion(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return Collection<int, Appellation>
     */
    public function getAppellations(): Collection
    {
        return $this->appellations;
    }

    public function addAppellation(Appellation $appellation): self
    {
        if (!$this->appellations->contains($appellation)) {
            $this->appellations[] = $appellation;
            $appellation->setCountryRegion($this);
        }

        return $this;
    }

    public function removeAppellation(Appellation $appellation): self
    {
        if ($this->appellations->removeElement($appellation)) {
            // set the owning side to null (unless already changed)
            if ($appellation->getCountryRegion() === $this) {
                $appellation->setCountryRegion(null);
            }
        }

        return $this;
    }
}
