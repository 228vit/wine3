<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product implements TimestampableInterface
{
    use TimestampableTrait;
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class, inversedBy="products")
     */
    private $country;

    /**
     * @ORM\ManyToOne(targetEntity=CountryRegion::class, inversedBy="products")
     */
    private $region;

    /**
     * @ORM\ManyToMany(targetEntity=Food::class, inversedBy="products")
     */
    private $foods;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="products")
     */
    private $vendor;
    /**
     * @ORM\ManyToOne(targetEntity=Supplier::class, inversedBy="products")
     */
    private $supplier;
    /**
     * @ORM\ManyToMany(targetEntity=WineCard::class, inversedBy="products")
     */
    private $winecards;

    /**
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="product")
     */
    private $offers;

    /**
     * @ORM\OneToMany(targetEntity=ProductGrapeSort::class, mappedBy="product", orphanRemoval=true, cascade={"persist"})
     */
    private $productGrapeSorts;

    /**
     * @ORM\OneToMany(targetEntity=ProductRating::class, mappedBy="product", orphanRemoval=true, cascade={"persist"})
     */
    private $productRatings;

    /**
     * @ORM\ManyToOne(targetEntity=WineColor::class, inversedBy="products")
     */
    private $wineColor;

    /**
     * @ORM\ManyToOne(targetEntity=WineSugar::class, cascade={"persist"})
     */
    private $wineSugar;

    /**
     * @ORM\ManyToOne(targetEntity=Admin::class, inversedBy="products")
     */
    private $editor;

    // end relations

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $metaKeywords;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $metaDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $announce;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $announcePic;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp" })
     */
    private $announcePicFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contentPic;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp" })
     */
    private $contentPicFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $extraPic;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp" })
     */
    private $extraPicFile;

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
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $viewsCount;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
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

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isEdited;

    /**
     * @ORM\OneToMany(targetEntity=EventProduct::class, mappedBy="product", orphanRemoval=true)
     */
    private $events;

    /**
     * @ORM\ManyToOne(targetEntity=Appellation::class, inversedBy="products")
     */
    private $appellation;


    public function __construct()
    {
        $this->viewsCount = 0;
        $this->priceStatus = 1;
        $this->decantation = true;
        $this->isActive = true;
        $this->foods = new ArrayCollection();
        $this->winecards = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->productGrapeSorts = new ArrayCollection();
        $this->productRatings = new ArrayCollection();
        $this->events = new ArrayCollection();
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

    public function getGrapeSortsAsString(string $glue = '; '): ?string
    {
        if (!empty($this->grapeSort)) {
            if (null !== $arr = json_decode($this->grapeSort, true)) {
                return is_array($arr) ? implode($glue, $arr) : '';
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

    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getAnnounce()
    {
        return $this->announce;
    }

    public function setAnnounce($announce)
    {
        $this->announce = $announce;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getAnnouncePic()
    {
        return $this->announcePic;
    }

    public function setAnnouncePic($announcePic)
    {
        $this->announcePic = $announcePic;
        return $this;
    }

    public function getContentPic()
    {
        return $this->contentPic;
    }

    public function setContentPic($contentPic)
    {
        $this->contentPic = $contentPic;
        return $this;
    }

    public function getExtraPic()
    {
        return $this->extraPic;
    }

    public function setExtraPic($extraPic)
    {
        $this->extraPic = $extraPic;
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

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function setViewsCount(int $viewsCount): Product
    {
        $this->viewsCount = $viewsCount;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getAnnouncePicFile(): ?File
    {
        return $this->announcePicFile;
    }

    public function setAnnouncePicFile(File $announcePicFile): Product
    {
        $this->announcePicFile = $announcePicFile;
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

    public function getContentPicFile(): ?File
    {
        return $this->contentPicFile;
    }

    public function setContentPicFile(File $contentPicFile): Product
    {
        $this->contentPicFile = $contentPicFile;
        return $this;
    }

    public function getExtraPicFile(): ?File
    {
        return $this->extraPicFile;
    }

    public function setExtraPicFile(File $extraPicFile): Product
    {
        $this->extraPicFile = $extraPicFile;
        return $this;
    }


    public function getSummary($delimiter = ', '): string
    {
        return implode($delimiter, array_filter([
            $this->getName(),
            (string)$this->getCountry(),
            null !== $this->getWineColor() ? $this->getWineColor()->__toString() : null,
            null !== $this->getWineSugar() ? $this->getWineSugar()->__toString() : null,
            $this->getYear().'г',
            $this->getAlcohol().'%',
        ]));
    }

    public function getShortSummary(): string
    {
        return implode(' ', array_filter([
            null !== $this->getWineColor() ? $this->getWineColor()->__toString() : null,
            null !== $this->getWineSugar() ? $this->getWineSugar()->__toString() : null,
            $this->getYear().'г',
            $this->getAlcohol().'%',
        ]));
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

    public function countWinecards(): int
    {
        return count($this->getWinecards()->toArray());
    }

    /**
     * @return Collection|WineCard[]
     */
    public function getWinecards(): Collection
    {
        return $this->winecards;
    }

    public function getWinecardIds(): array
    {
        $res = [];
        foreach ($this->getWinecards() as $wineCard) {
            $res[] = $wineCard->getId();
        }

        return $res;
    }

    public function addWinecard(WineCard $winecard): self
    {
        if (!$this->winecards->contains($winecard)) {
            $this->winecards[] = $winecard;
        }

        return $this;
    }

    public function removeWinecard(WineCard $winecard): self
    {
        $this->winecards->removeElement($winecard);

        return $this;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setProduct($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getProduct() === $this) {
                $offer->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductGrapeSort[]
     */
    public function getProductGrapeSorts(): Collection
    {
        return $this->productGrapeSorts;
    }

    public function addProductGrapeSort(ProductGrapeSort $productGrapeSort): self
    {
        if (!$this->productGrapeSorts->contains($productGrapeSort)) {
            $this->productGrapeSorts[] = $productGrapeSort;
            $productGrapeSort->setProduct($this);
        }

        return $this;
    }

    public function removeProductGrapeSort(ProductGrapeSort $productGrapeSort): self
    {
        if ($this->productGrapeSorts->removeElement($productGrapeSort)) {
            // set the owning side to null (unless already changed)
            if ($productGrapeSort->getProduct() === $this) {
                $productGrapeSort->setProduct(null);
            }
        }

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
            $productRating->setProduct($this);
        }

        return $this;
    }

    public function removeProductRating(ProductRating $productRating): self
    {
        if ($this->productRatings->removeElement($productRating)) {
            // set the owning side to null (unless already changed)
            if ($productRating->getProduct() === $this) {
                $productRating->setProduct(null);
            }
        }

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

    public function getWineSugar(): ?WineSugar
    {
        return $this->wineSugar;
    }

    public function setWineSugar(?WineSugar $wineSugar): self
    {
        $this->wineSugar = $wineSugar;

        return $this;
    }

    public function getEditor(): ?Admin
    {
        return $this->editor;
    }

    public function setEditor(?Admin $editor): self
    {
        $this->editor = $editor;

        return $this;
    }

    public function getIsEdited(): ?bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(?bool $isEdited): self
    {
        $this->isEdited = $isEdited;

        return $this;
    }

    public function productGrapeSortsAsArray(): iterable
    {
        foreach ($this->getProductGrapeSorts() as $grapeSort) {
            yield [$grapeSort->getGrapeSort()->getName(), $grapeSort->getValue()];
        }
    }

    public function productGrapeSortsAsString(): iterable
    {
        foreach ($this->productGrapeSortsAsArray() as [$key, $value]) {
            yield sprintf('%s: %s%%', $key, $value);
        };
    }

    /**
     * @return Collection<int, EventProduct>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(EventProduct $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setProduct($this);
        }

        return $this;
    }

    public function removeEvent(EventProduct $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getProduct() === $this) {
                $event->setProduct(null);
            }
        }

        return $this;
    }

    public function getAppellation(): ?Appellation
    {
        return $this->appellation;
    }

    public function setAppellation(?Appellation $appellation): self
    {
        $this->appellation = $appellation;

        return $this;
    }

}
