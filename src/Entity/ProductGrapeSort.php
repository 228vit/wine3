<?php

namespace App\Entity;

use App\Repository\ProductGrapeSortRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductGrapeSortRepository::class)
 */
class ProductGrapeSort
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=GrapeSort::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $grapeSort;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productGrapeSorts", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     */
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrapeSort(): ?GrapeSort
    {
        return $this->grapeSort;
    }

    public function setGrapeSort(?GrapeSort $grapeSort): self
    {
        $this->grapeSort = $grapeSort;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->getId();
    }
}
