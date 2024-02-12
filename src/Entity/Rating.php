<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RatingRepository::class)
 */
class Rating
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
    private $maxRating;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=ProductRating::class, mappedBy="rating", orphanRemoval=true, cascade={"persist"})
     */
    private $productRatings;

    public function __construct()
    {
        $this->maxRating = 100;
        $this->productRatings = new ArrayCollection();
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

    public function getMaxRating(): ?int
    {
        return $this->maxRating;
    }

    public function setMaxRating(int $maxRating): self
    {
        $this->maxRating = $maxRating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|ProductRating[]
     */
    public function getProductRatings(): Collection
    {
        return $this->productRatings;
    }

    public function addProductRating(ProductRating $productRating): self
    {
        if (!$this->productRatings->contains($productRating)) {
            $this->productRatings[] = $productRating;
            $productRating->setRating($this);
        }

        return $this;
    }

    public function removeProductRating(ProductRating $productRating): self
    {
        if ($this->productRatings->removeElement($productRating)) {
            // set the owning side to null (unless already changed)
            if ($productRating->getRating() === $this) {
                $productRating->setRating(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
