<?php

namespace App\Entity;

use App\Repository\VendorPicRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=VendorPicRepository::class)
 */
class VendorPic
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vendor::class, inversedBy="pics", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $vendor;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pic;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp" })
     */
    private $picFile;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPic(): ?string
    {
        return $this->pic;
    }

    public function setPic(string $pic): self
    {
        $this->pic = $pic;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPicFile(): ?File
    {
        return $this->picFile;
    }

    public function setPicFile(File $picFile): self
    {
        $this->picFile = $picFile;
        return $this;
    }
}
