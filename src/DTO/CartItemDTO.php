<?php


namespace App\DTO;


use App\Entity\Offer;

class CartItemDTO
{
    public $offer;
    public $quantity;
    public $amount;

    public function __construct(Offer $offer, int $quantity)
    {
        $this->offer = $offer;
        $this->quantity = $quantity;
        $this->amount = round($offer->getPrice() * $quantity, 2);
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

}