<?php

namespace App\Controller\Front;

use App\DTO\CartItemDTO;
use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\WineCard;
use App\Form\Front\CabinetWineCardType;
use App\Repository\OfferRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\WineCardRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class WineCardController extends AbstractController
{
    use FrontTraitController;

    /**
     * @Route("/cabinet/winecard/index", name="cabinet_wine_card_index", methods={"GET"})
     */
    public function index(WineCardRepository $wineCardRepository): Response
    {
        $user = $this->getUser();

        return $this->render('front/wine_card/index.html.twig', [
            'wine_cards' => $wineCardRepository->getAllByUser($user),
        ]);
    }

    /**
     * @Route("/cabinet/winecard/new", name="cabinet_wine_card_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $user = $this->getUser();

        $wineCard = new WineCard();
        $wineCard->setUser($user);

        $form = $this->createForm(CabinetWineCardType::class, $wineCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($wineCard);
            $this->em->flush();

            return $this->redirectToRoute('cabinet_wine_card_index');
        }

        return $this->render('front/wine_card/new.html.twig', [
            'wine_card' => $wineCard,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/cabinet/winecard/{id}", name="cabinet_wine_card_show", methods={"GET"})
     */
    public function show(WineCard $wineCard): Response
    {
        $this->checkOwner($wineCard);

        return $this->render('front/wine_card/show.html.twig', [
            'wineCard' => $wineCard,
        ]);
    }

    /**
     * @Route("/cabinet/winecard/{id}/edit", name="cabinet_wine_card_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, WineCard $wineCard): Response
    {
        $this->checkOwner($wineCard);

        $form = $this->createForm(CabinetWineCardType::class, $wineCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($wineCard);
            $this->em->flush();

            $this->addFlash('success', 'Данные успешно сохранены.');
        }

        return $this->render('front/wine_card/edit.html.twig', [
            'wineCard' => $wineCard,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/cabinet/winecard/{id}/add_to_cart", name="cabinet_wine_card_add_to_cart", methods={"GET","POST"})
     */
    public function addToCart(Request $request, WineCard $wineCard): Response
    {
        $this->checkOwner($wineCard);

        $session = $this->get('session');
        $cart = $session->get('cart', null);

        $offers = $request->request->get('offer', []);
        foreach ($offers as $offerId => $qty) {
            if ($qty < 1) { continue; }
            $cart[$offerId] = $qty;
        }

        if (null === $cart) {
            $this->addFlash('error', 'Количество товаров не может быть нулевым.');
            return $this->redirectToRoute('cabinet_wine_card_show', ['id' => $wineCard->getId()]);
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('cabinet_wine_card_checkout', [
            'id' => $wineCard->getId(),
        ]);
    }

    /**
     * @Route("/cabinet/winecard/{id}/checkout", name="cabinet_wine_card_checkout", methods={"GET","POST"})
     */
    public function checkout(WineCard $wineCard,
                             OfferRepository $offerRepository): Response
    {
        $session = $this->get('session');
        $cart = $session->get('cart', false);

        if (false === $cart) {
            $this->addFlash('error', 'Ошибка при создании заказа!');

            return $this->redirectToRoute('cabinet_wine_card_index');
        }
        $offer_ids = array_keys($cart);
        $offers = $offerRepository->getByIds($offer_ids);

        $cartItems = [];
        $total = 0;

        /** @var Offer $offer */
        foreach ($offers as $offer) {
            $cardItem = new CartItemDTO($offer, $cart[$offer->getId()]);
            $cartItems[] = $cardItem;
            $total += $cardItem->getAmount();
        }

        return $this->render('front/order/checkout.html.twig', [
            'cartItems' => $cartItems,
            'wineCard' => $wineCard,
            'totalAmount' => $total,
        ]);

    }

    /**
     * @Route("/cabinet/winecard/{id}/order", name="cabinet_wine_card_order", methods={"GET","POST"})
     */
    public function order(Request $request, WineCard $wineCard,
                          MailerInterface $mailer,
                          OrderItemRepository $orderItemRepository,
                          OfferRepository $offerRepository): Response
    {
        $session = $this->get('session');
        $cart = array_filter($session->get('cart', null));

        if (null === $cart) {
            $this->addFlash('error', 'Ваша корзина пуста.');
            return $this->redirectToRoute('cabinet_wine_card_index');
        }

        $order = (new Order())
            ->setUser($this->getUser())
            ->setWineCard($wineCard)
            ->setNote($request->request->get('note', ''))
            ->setAddress($request->request->get('address', ''))
            ->setDelivery($request->request->get('delivery', ''))
        ;

        $this->em->persist($order);
        $this->em->flush();

        foreach ($cart as $offerId => $qty) {
            $offer = $offerRepository->find($offerId);

            if ($qty <= 0 OR null === $offer) {
                continue;
            }

            $orderItem = (new OrderItem())
                ->setOffer($offer)
                ->setOwner($order)
                ->setQuantity($qty)
                ->setPrice($offer->getPrice())
            ;

            $this->em->persist($orderItem);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ошибка при создании заказа!');

            return $this->redirectToRoute('cabinet_wine_card_index');
        }

        // todo: попытаться разобраться с фигнёй со связанными записями
        $senderEmail = $this->getParameter('mailer_sender_email');
        $orderItems = $orderItemRepository->findBy(['owner' => $order]); //$order->getOrderProducts();

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($wineCard->getUser()->getEmail())
            ->subject(sprintf('Ваш новый заказ № %s на сайте WineDows', $order->getId()))
            ->htmlTemplate('front/email_templates/order.html.twig')
            ->context([
                'order' => $order,
                'orderItems' => $orderItems,
                'wineCard' => $wineCard,
                'user' => $this->getUser(),
            ])
        ;
        $mailer->send($email);

        $orderItems = $orderItemRepository->joinedAllSortedBySupplier($order);
        $supplierProducts = [];
        $suppliers = [];

        /** @var OrderItem $orderItem */
        foreach ($orderItems as $orderItem) {
            $supplier = $orderItem->getOffer()->getSupplier();
            if ($supplier instanceof Supplier) {
                $suppliers[$supplier->getId()] = $orderItem->getOffer()->getSupplier();
                $supplierProducts[$supplier->getId()][] = $orderItem;
            }
        }

        foreach ($suppliers as $id => $supplier) {
            $email = (new TemplatedEmail())
                ->from($senderEmail)
                ->to($supplier->getEmail())
                ->subject(sprintf('[WineDows] новый заказ № %s на поставку вашей продукции', $order->getId()))
                ->htmlTemplate('front/email_templates/order.html.twig')
                ->context([
                    'order' => $order,
                    'orderItems' => $supplierProducts[$id],
                    'wineCard' => $wineCard,
                    'user' => $this->getUser(),
                ])
            ;

            // todo: use mail sender service
            $mailer->send($email);

        }

        $session->set('cart', false);

        return $this->redirectToRoute('cabinet_wine_card_order_success', [
            'id' => $wineCard->getId(),
            'uuid' => $order->getUuid(),
        ]);
    }

    /**
     * @Route("/cabinet/winecard/{id}/order_success/{uuid}", name="cabinet_wine_card_order_success", methods={"GET","POST"})
     */
    public function orderSuccess(WineCard $wineCard, string $uuid, OrderRepository $orderRepository)
    {
        $order = $orderRepository->findOneBy(['uuid' => $uuid]);

        if (null === $order) {
            $this->addFlash('error', 'Нерверные параметры.');
            return $this->redirectToRoute('cabinet_wine_card_index');
        }

        return $this->render('front/order/order_success.html.twig', [
            'order' => $order,
            'wineCard' => $wineCard,
        ]);
    }

    // todo: понять, надо ли разделять корзины для разных винных карт
    /**
     * @Route("/cabinet/winecard/{id}/clear_cart", name="cabinet_clear_cart", methods={"GET","POST"})
     */
    public function clearCart(Request $request, WineCard $wineCard): Response
    {
        $session = $this->get('session');
        $session->set('cart', null);
        $this->addFlash('success', 'Корзина очищена.');

        return $this->redirectToRoute('cabinet_wine_card_index');
    }

    /**
     * @Route("/cabinet/winecard/{id}/pdf", name="cabinet_wine_card_pdf", methods={"GET"})
     */
    public function pdf(WineCard $wineCard, WineCardRepository $wineCardRepository)
    {


        return $this->render('front/order/order_success.html.twig', [
            'order' => $order,
            'wineCard' => $wineCard,
        ]);
    }


    /**
     * @Route("/cabinet/winecard/{id}", name="cabinet_wine_card_delete", methods={"DELETE"})
     */
    public function delete(Request $request, WineCard $wineCard): Response
    {
        if ($this->isCsrfTokenValid('delete'.$wineCard->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($wineCard);
            $entityManager->flush();
        }

        return $this->redirectToRoute('cabinet_wine_card_index');
    }

    private function checkOwner(WineCard $wineCard)
    {
        if ($this->getUser() !== $wineCard->getUser()) {
            $this->addFlash('error', 'Неверный владелец!');

            return $this->redirectToRoute('cabinet_wine_card_index');
        }
    }

    public function countMyWineCards(WineCardRepository $repository): Response
    {
        return new Response($repository->countMyWineCards($this->getUser()));
    }

}
