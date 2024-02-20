<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=OfferRepository::class)
 */
class Offer implements TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="offers")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Supplier::class, inversedBy="offers")
     */
    private $supplier;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class)
     */
    private $vendor;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     */
    private $country;

    /**
     * @ORM\ManyToOne(targetEntity=CountryRegion::class)
     */
    private $region;

    /**
     * @ORM\ManyToMany(targetEntity=Food::class)
     */
    private $foods;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $productCode;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="integer", options={"default": 1})
     */
    private $priceStatus;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * Wine sugar!
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="float")
     */
    private $alcohol;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $grapeSort;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ratings;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $year;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="float")
     * @ORM\Column(type="float")
     */
    private $volume;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $serveTemperature;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $decantation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $appellation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $packing;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fermentation;

    /**
     * Срок выдержки (необязательно)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aging;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $agingType;

    /**
     * @ORM\ManyToOne(targetEntity=ImportLog::class, inversedBy="products")
     */
    private $import;

    public function __construct()
    {
        $this->priceStatus = 1;
        $this->decantation = true;
        $this->foods = new ArrayCollection();
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

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }

    public function getVendor(): ?Vendor
    {
        return $this->vendor;
    }

    public function setVendor(?Vendor $vendor): self
    {
        $this->vendor = $vendor;

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

    public function getRegion(): ?CountryRegion
    {
        return $this->region;
    }

    public function setRegion(?CountryRegion $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAlcohol(): ?int
    {
        return $this->alcohol;
    }

    public function setAlcohol(float $alcohol): self
    {
        $this->alcohol = $alcohol;

        return $this;
    }

    public function getGrapeSort(): ?string
    {
        return $this->grapeSort;
    }

    public function getGrapeSortsAsString($glue = ', '): ?string
    {
        if (!empty($this->grapeSort)) {
            if (null !== $arr = json_decode($this->grapeSort, true)) {
                $out = [];
                foreach ($arr as $sort => $value) {
                    $out[] = sprintf('%s: %s', $sort, $value);
                }
                return is_array($out) ? implode($glue, $out) : '';
            } else {
                return (string)$this->grapeSort;
            }
        }

        return '';
    }

    public function setGrapeSort(?string $grapeSort): self
    {
        $this->grapeSort = $grapeSort;

        return $this;
    }

    public function getRatings(): ?string
    {
        return $this->ratings;
    }

    public function setRatings(?string $ratings): self
    {
        $this->ratings = $ratings;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getFoods(): Collection
    {
        return $this->foods;
    }

    public function getFoodsAsString(): ?string
    {
        $arr = [];
        foreach ($this->foods as $food) {
            $arr[] = (string)$food;
        }

        return 0 !== count($arr) ? implode('; ', $arr) : '';
    }

    public function addFood(Food $food): self
    {
        if (!$this->foods->contains($food)) {
            $this->foods[] = $food;
        }

        return $this;
    }

    public function removeFood(Food $food): self
    {
        $this->foods->removeElement($food);

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }


    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceStatus(): ?int
    {
        return $this->priceStatus;
    }

    public function setPriceStatus(int $priceStatus): self
    {
        $this->priceStatus = $priceStatus;

        return $this;
    }

    public function getServeTemperature(): ?string
    {
        return $this->serveTemperature;
    }

    public function setServeTemperature(?string $serveTemperature): self
    {
        $this->serveTemperature = $serveTemperature;

        return $this;
    }

    public function getDecantation(): ?bool
    {
        return $this->decantation;
    }

    public function setDecantation(bool $decantation): self
    {
        $this->decantation = $decantation;

        return $this;
    }

    public function getFermentation(): ?string
    {
        return $this->fermentation;
    }

    public function setFermentation(?string $fermentation): self
    {
        $this->fermentation = $fermentation;

        return $this;
    }

    public function getAgingType(): ?string
    {
        return $this->agingType;
    }

    public function setAgingType(?string $agingType): self
    {
        $this->agingType = $agingType;

        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): self
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getPicSubDir(): int
    {
        return floor($this->id / 200);
    }

    public function getPacking(): ?string
    {
        return $this->packing;
    }

    public function setPacking($packing)
    {
        $this->packing = $packing;
        return $this;
    }

    public function getAppellation(): ?string
    {
        return $this->appellation;
    }

    public function setAppellation($appellation)
    {
        $this->appellation = $appellation;
        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getAging(): ?string
    {
        return $this->aging;
    }

    public function setAging($aging): self
    {
        $this->aging = $aging;
        return $this;
    }

    public function getImport(): ?ImportLog
    {
        return $this->import;
    }

    public function setImport(?ImportLog $import): self
    {
        $this->import = $import;

        return $this;
    }

    public function isCreated(): bool
    {
        return $this->getCreatedAt() == $this->getUpdatedAt();
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

    public function getSummary($delimiter = ', '): string
    {
        return implode($delimiter, [
            $this->getName(),
            (string)$this->getCountry(),
            $this->getColor(),
            $this->getType(),
            $this->getYear().'г',
            $this->getAlcohol().'%',
        ]);
    }

    public function getShortSummary(): string
    {
        return implode(' ', [
            $this->getColor(),
            $this->getType(),
            $this->getYear().'г',
            $this->getAlcohol().'%',
        ]);
    }

}
