<?php


namespace App\DTO;


use App\Entity\Order;

class OrderInfoDTO
{
    private $order;
    private $bottleCnt;
    private $orderAmount;

    public function __construct(Order $order, int $bottleCnt, float $orderAmount)
    {
        $this->order = $order;
        $this->bottleCnt = $bottleCnt;
        $this->orderAmount = $orderAmount;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getBottleCnt(): int
    {
        return $this->bottleCnt;
    }

    public function getOrderAmount(): float
    {
        return $this->orderAmount;
    }



}