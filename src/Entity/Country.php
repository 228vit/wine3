<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CountryRepository::class)
 */
class Country
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
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $codeAlpha2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $flagPic;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp", "image/svg" })
     */
    private $flagPicFile;

    /**
     * @ORM\OneToMany(targetEntity=CountryRegion::class, mappedBy="country", orphanRemoval=true)
     */
    private $regions;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="country")
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity=CountryAlias::class, mappedBy="Country", orphanRemoval=true, cascade={"persist"})
     */
    private $aliases;

    /**
     * @ORM\Column(type="string", length=64, nullable=true, options={"default": "new_world"})
     */
    private $worldPart;

    public const WORLD_PARTS = [
        'new_world' => 'Новый свет',
        'old_world' => 'Старый свет',
    ];

    public const WORLD_PARTS_INVERSED = [
        'Новый свет' => 'new_world',
        'Старый свет' => 'old_world',
    ];


    public function __construct()
    {
        $this->regions = new ArrayCollection();
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

    public function getFlagPic(): ?string
    {
        return $this->flagPic;
    }

    public function setFlagPic(?string $flagPic): self
    {
        $this->flagPic = $flagPic;

        return $this;
    }

    /**
     * @return Collection|CountryRegion[]
     */
    public function getRegions(): Collection
    {
        return $this->regions;
    }

    public function addRegion(CountryRegion $region): self
    {
        if (!$this->regions->contains($region)) {
            $this->regions[] = $region;
            $region->setCountry($this);
        }

        return $this;
    }

    public function removeRegion(CountryRegion $region): self
    {
        if ($this->regions->removeElement($region)) {
            // set the owning side to null (unless already changed)
            if ($region->getCountry() === $this) {
                $region->setCountry(null);
            }
        }

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
            $product->setCountry($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getCountry() === $this) {
                $product->setCountry(null);
            }
        }

        return $this;
    }

    public function getFlagPicFile(): ?File
    {
        return $this->flagPicFile;
    }

    public function setFlagPicFile(File $flagPicFile): Country
    {
        $this->flagPicFile = $flagPicFile;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getCodeAlpha2(): ?string
    {
        return null === $this->codeAlpha2 ? 'eu' : $this->codeAlpha2;
    }

    public function setCodeAlpha2(?string $codeAlpha2): self
    {
        $this->codeAlpha2 = $codeAlpha2;

        return $this;
    }

    /**
     * @return Collection|CountryAlias[]
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    public function addAlias(CountryAlias $alias): self
    {
        if (!$this->aliases->contains($alias)) {
            $this->aliases[] = $alias;
            $alias->setCountry($this);
        }

        return $this;
    }

    public function removeAlias(CountryAlias $alias): self
    {
        if ($this->aliases->removeElement($alias)) {
            // set the owning side to null (unless already changed)
            if ($alias->getCountry() === $this) {
                $alias->setCountry(null);
            }
        }

        return $this;
    }

    public function getWorldPart(): ?string
    {
        return $this->worldPart;
    }

    public function setWorldPart(?string $worldPart): self
    {
        $this->worldPart = $worldPart;

        return $this;
    }
}
