<?php

namespace App\Entity;

use App\Repository\ImportYmlRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;

/**
 * @ORM\Entity(repositoryClass=ImportYmlRepository::class)
 */
class ImportYml implements TimestampableInterface
{
    use TimestampableTrait;

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
     * @ORM\ManyToOne(targetEntity=Supplier::class)
     */
    private $supplier;

    /**
     * @ORM\Column(type="integer")
     */
    private $stage;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $url;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $fieldsMapping;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $paramsMapping;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $countriesMapping;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $regionsMapping;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $vendorsMapping;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $appellationsMapping;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isComplete;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rotatePicAngle;

    /**
     * @ORM\OneToMany(targetEntity=Offer::class, mappedBy="importYml")
     */
    private $offers;

    public function __construct()
    {
        $this->rotatePicAngle = 0;
        $this->stage = 1;
        $this->isComplete = false;
        $this->offers = new ArrayCollection();
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getStage(): ?int
    {
        return $this->stage;
    }

    public function setStage(int $stage): self
    {
        $this->stage = $stage;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getFieldsMapping(): ?string
    {
        return $this->fieldsMapping;
    }

    public function setFieldsMapping(?string $fieldsMapping): self
    {
        $this->fieldsMapping = $fieldsMapping;

        return $this;
    }

    public function getParamsMapping(): ?string
    {
        return $this->paramsMapping;
    }

    public function setParamsMapping(?string $paramsMapping): self
    {
        $this->paramsMapping = $paramsMapping;

        return $this;
    }

    public function getIsComplete(): ?bool
    {
        return $this->isComplete;
    }

    public function setIsComplete(bool $isComplete): self
    {
        $this->isComplete = $isComplete;

        return $this;
    }

    public function getCountriesMapping(): ?string
    {
        return $this->countriesMapping;
    }

    public function setCountriesMapping(?string $countriesMapping): self
    {
        $this->countriesMapping = $countriesMapping;
        return $this;
    }

    public function getRegionsMapping(): ?string
    {
        return $this->regionsMapping;
    }

    public function setRegionsMapping(?string $regionsMapping): self
    {
        $this->regionsMapping = $regionsMapping;
        return $this;
    }

    public function getVendorsMapping(): ?string
    {
        return $this->vendorsMapping;
    }

    public function setVendorsMapping(?string $vendorsMapping): self
    {
        $this->vendorsMapping = $vendorsMapping;
        return $this;
    }

    public function getAppellationsMapping()
    {
        return $this->appellationsMapping;
    }

    public function setAppellationsMapping($appellationsMapping)
    {
        $this->appellationsMapping = $appellationsMapping;
        return $this;
    }

    public function getRotatePicAngle(): ?int
    {
        return $this->rotatePicAngle;
    }

    public function setRotatePicAngle(?int $rotatePicAngle): self
    {
        $this->rotatePicAngle = $rotatePicAngle;

        return $this;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setImportYml($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getImportYml() === $this) {
                $offer->setImportYml(null);
            }
        }

        return $this;
    }



}
