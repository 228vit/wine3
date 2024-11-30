<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
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
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $coordinates;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity=Vendor::class, inversedBy="events")
     */
    private $vendors;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $slug;

    /**
     * @ORM\ManyToMany(targetEntity=Supplier::class, inversedBy="events")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $suppliers;

    /**
     * @ORM\OneToMany(targetEntity=EventProduct::class, mappedBy="event", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity=EventPic::class, mappedBy="event")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $eventPics;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $collage;

    /**
     * @var File
     *
     * @Assert\File(mimeTypes={ "image/jpg", "image/jpeg", "image/png", "image/webp" })
     */
    private $collagePicFile;

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
     * @ORM\ManyToOne(targetEntity=Supplier::class)
     */
    private $organizer;

    public function __construct()
    {
        $this->vendors = new ArrayCollection();
        $this->suppliers = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->eventPics = new ArrayCollection();
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

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCoordinates(): ?string
    {
        return $this->coordinates;
    }

    public function setCoordinates(?string $coordinates): self
    {
        $this->coordinates = $coordinates;

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

    public function getVendors(): Collection
    {
        return $this->vendors;
    }

    public function addVendor(Vendor $vendor): self
    {
        if (!$this->vendors->contains($vendor)) {
            $this->vendors[] = $vendor;
        }

        return $this;
    }

    public function removeVendor(Vendor $vendor): self
    {
        $this->vendors->removeElement($vendor);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function getSuppliers(): Collection
    {
        return $this->suppliers;
    }

    public function addSupplier(Supplier $supplier): self
    {
        if (!$this->suppliers->contains($supplier)) {
            $this->suppliers[] = $supplier;
        }

        return $this;
    }

    public function removeSupplier(Supplier $supplier): self
    {
        $this->suppliers->removeElement($supplier);

        return $this;
    }

    /**
     * @return Collection<int, EventProduct>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(EventProduct $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setEvent($this);
        }

        return $this;
    }

    public function removeProduct(EventProduct $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getEvent() === $this) {
                $product->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventPic>
     */
    public function getEventPics(): Collection
    {
        return $this->eventPics;
    }

    public function addEventPic(EventPic $eventPic): self
    {
        if (!$this->eventPics->contains($eventPic)) {
            $this->eventPics[] = $eventPic;
            $eventPic->setEvent($this);
        }

        return $this;
    }

    public function removeEventPic(EventPic $eventPic): self
    {
        if ($this->eventPics->removeElement($eventPic)) {
            // set the owning side to null (unless already changed)
            if ($eventPic->getEvent() === $this) {
                $eventPic->setEvent(null);
            }
        }

        return $this;
    }

    public function getCollage(): ?string
    {
        return $this->collage;
    }

    public function setCollage(?string $collage): self
    {
        $this->collage = $collage;

        return $this;
    }

    public function getCollagePicFile(): ?File
    {
        return $this->collagePicFile;
    }

    public function setCollagePicFile(?File $collagePicFile): Event
    {
        $this->collagePicFile = $collagePicFile;
        return $this;
    }

    public function getEventTime(): ?string
    {
        return $this->getDateTime()->format('H:i');
    }

    public function getAnnouncePic(): ?string
    {
        return $this->announcePic;
    }

    public function setAnnouncePic(?string $announcePic): self
    {
        $this->announcePic = $announcePic;

        return $this;
    }

    public function getAnnouncePicFile(): ?File
    {
        return $this->announcePicFile;
    }

    public function setAnnouncePicFile(File $announcePicFile): Event
    {
        $this->announcePicFile = $announcePicFile;
        return $this;
    }

    public function getOrganizer(): ?Supplier
    {
        return $this->organizer;
    }

    public function setOrganizer(?Supplier $organizer): self
    {
        $this->organizer = $organizer;

        return $this;
    }


}
