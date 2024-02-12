<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
//use Knp\DoctrineBehaviors\Contract\Entity\TimestampableInterface;
//use Knp\DoctrineBehaviors\Contract\Entity\UuidableInterface;
//use Knp\DoctrineBehaviors\Model\Timestampable\TimestampableTrait;
//use Knp\DoctrineBehaviors\Model\Uuidable\UuidableMethodsTrait;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="orders")
 */
class Order //implements TimestampableInterface, UuidableInterface
{
    public const STATUSES = [
        0 => 'new',
        1 => 'in process',
        2 => 'finished',
        99 => 'cancelled',
    ];

    public const DELIVERY = [
        'courier' => 'курьер',
        'pickup' => 'самовывоз',
        'logistics company' => 'транспортной компанией',
    ];

//    use TimestampableTrait;
//    use UuidableMethodsTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $uuid;

    /**
* @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $delivery;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\OneToMany(targetEntity=OrderProduct::class, mappedBy="userOrder", orphanRemoval=true, fetch="EAGER")
     */
    private $orderProducts;

    /**
     * @ORM\ManyToOne(targetEntity=WineCard::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wineCard;

    /**
     * @ORM\OneToMany(targetEntity=OrderItem::class, mappedBy="owner", orphanRemoval=true)
     */
    private $orderItems;

    public function __construct()
    {
        // todo: use enums for status
        $this->status = 0;
        $this->delivery = 'courier';
        $this->orderProducts = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDelivery(): ?string
    {
        return $this->delivery;
    }

    public function setDelivery(string $delivery): self
    {
        $this->delivery = $delivery;

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

    /**
     * @return Collection|OrderProduct[]
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): self
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts[] = $orderProduct;
            $orderProduct->setUserOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): self
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getUserOrder() === $this) {
                $orderProduct->setUserOrder(null);
            }
        }

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getWineCard(): ?WineCard
    {
        return $this->wineCard;
    }

    public function setWineCard(?WineCard $wineCard): self
    {
        $this->wineCard = $wineCard;

        return $this;
    }

    public function getStatusAsString(): string
    {
        return self::STATUSES[$this->getStatus()] ?? 'undefined';
    }

    public function __toString(): string
    {
        return sprintf('Заказ №%s, пользователь: %s, дата: %s',
            $this->getId(),
            (string)$this->getWineCard(),
            $this->getCreatedAt()->format('d.m.Y')
        );
    }

    /**
     * @return Collection|OrderItem[]
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setOwner($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOwner() === $this) {
                $orderItem->setOwner(null);
            }
        }

        return $this;
    }
}
