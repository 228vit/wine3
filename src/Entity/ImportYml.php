<?php

namespace App\Entity;

use App\Repository\ImportYmlRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
     * @ORM\Column(type="boolean")
     */
    private $isComplete;

    public function __construct()
    {
        $this->stage = 1;
        $this->isComplete = false;
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
}
