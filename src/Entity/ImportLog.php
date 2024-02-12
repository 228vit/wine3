<?php

namespace App\Entity;

use App\Repository\ImportLogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
//use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
//use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;


/**
 * @ORM\Entity(repositoryClass=ImportLogRepository::class)
 */
class ImportLog // implements TimestampableInterface
{
    public const TOTAL_STAGES = 4;

//    use Timestampable;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Admin::class, inversedBy="importLogs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $admin;

    /**
     * @ORM\ManyToOne(targetEntity=Supplier::class, inversedBy="imports")
     * @ORM\JoinColumn(nullable=false)
     */
    private $supplier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $fieldRelations;

    /**
     * @ORM\Column(type="integer")
     */
    private $stage;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $fileContainHeader;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isComplete;

    /**
     * CSV file name w/o path
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $csv;

    /**
     * @ORM\Column(type="string", length=1, nullable=false, options={"default": ";"})
     */
    private $csvDelimiter;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "application/octet-stream", "text/plain", "text/csv" })
     */
    private $csvFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $fieldsMapping;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="import")
     */
    private $products;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     */
    private $category;

    public function __construct()
    {
        $this->stage = 1;
        $this->fileContainHeader = false;
        $this->isComplete = false;
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function setAdmin(?Admin $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getFieldRelations(): ?string
    {
        return $this->fieldRelations;
    }

    public function setFieldRelations(string $fieldRelations): self
    {
        $this->fieldRelations = $fieldRelations;

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

    public function getIsComplete(): ?bool
    {
        return $this->isComplete;
    }

    public function setIsComplete(bool $isComplete): self
    {
        $this->isComplete = $isComplete;

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

    public function getCsv(): ?string
    {
        return $this->csv;
    }

    public function setCsv(string $csv): self
    {
        $this->csv = $csv;

        return $this;
    }

    public function getCsvFile(): ?File
    {
        return $this->csvFile;
    }

    public function setCsvFile(File $csvFile): ImportLog
    {
        $this->csvFile = $csvFile;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }


    public function isFileContainHeader(): bool
    {
        return $this->fileContainHeader;
    }

    public function setFileContainHeader(bool $fileContainHeader): ImportLog
    {
        $this->fileContainHeader = $fileContainHeader;
        return $this;
    }

    public function getStageOf(): string
    {
        return sprintf('%s of %s',
            $this->getStage(),
            self::TOTAL_STAGES
        );
    }

    public function getCsvDelimiter(): string
    {
        return $this->csvDelimiter;
    }

    public function setCsvDelimiter($csvDelimiter)
    {
        $this->csvDelimiter = $csvDelimiter;
        return $this;
    }


    public function __toString(): string
    {
        return sprintf('%s %s от %s',
            $this->getName(),
            (string)$this->getSupplier(),
            $this->updatedAt->format('d.m.Y')
        );
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
            $product->setImport($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getImport() === $this) {
                $product->setImport(null);
            }
        }

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSummary(): string
    {
        return sprintf('%s от: %s, поставщик: %s',
            $this->getName(), $this->getUpdatedAt()->format('d.m.Y'), (string)$this->getSupplier());
    }
}
