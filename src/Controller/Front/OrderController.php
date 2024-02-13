<?php

namespace App\Controller\Front;

use App\DTO\OrderInfoDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cabinet/orders")
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/", name="cabinet_order_index")
     */
    public function index(OrderRepository $orderRepository): Response
    {
        // todo: paging?
        $orders = $orderRepository->getJoinedAll($this->getUser());

        $result = [];
        /** @var Order $order */
        foreach ($orders as $order) {
            $bottleCnt = 0;
            $orderAmount = 0;
            /** @var OrderItem $orderItem */
            foreach ($order->getOrderItems() as $orderItem) {
                $bottleCnt += $orderItem->getQuantity();
                $orderAmount += $bottleCnt * $orderItem->getPrice();
            }

            $result[] = new OrderInfoDTO($order, $bottleCnt, $orderAmount);
        }

        return $this->render('front/order/index.html.twig', [
            'result' => $result,
        ]);
    }

    /**
     * @Route("/{uuid}/show", name="cabinet_order_show")
     */
    public function show(Order $order, OrderRepository $orderRepository): Response
    {

        return $this->render('front/order/show.html.twig', [
            'order' => $order,
            'wineCard' => $order->getWineCard(),
        ]);
    }

    public function countMyOrders(OrderRepository $orderRepository): Response
    {
        return new Response($orderRepository->countMyOrders($this->getUser()));
    }
}
