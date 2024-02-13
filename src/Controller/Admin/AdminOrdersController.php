<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\User;
use App\Filter\OrdersFilter;
use App\Form\OrderType;
use App\Form\UserType;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/backend/orders")
 */
class AdminOrdersController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'orders';
    CONST ENTITY_NAME = 'Order';
    CONST NS_ENTITY_NAME = 'App:Order';

    /**
     * Lists all user entities.
     *
     * @Route("/index", name="backend_orders_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, OrdersFilter::class, 'id', 'DESC');

        return $this->render('admin/order/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'order.id',
                    'sortable' => true,
                ],
                'a.user' => [
                    'title' => 'User',
                    'row_field' => 'user',
                    'sortable' => false,
                ],
                'a.status' => [
                    'title' => 'Status',
                    'row_field' => 'status',
                    'sortable' => false,
                ],
                'a.delivery' => [
                    'title' => 'Delivery',
                    'row_field' => 'delivery',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="backend_orders_edit", methods={"GET", "POST"})
     */
    public function editAction(Order $order,
                               Request $request,
                               OrderRepository $orderRepository,
                               OrderProductRepository $orderProductRepository)
    {
        $deleteForm = $this->createDeleteForm($order);
        $editForm = $this->createForm(OrderType::class, $order);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->persist($order);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_orders_edit', array('id' => $order->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $orderProducts = $orderProductRepository->joinedAllSortedBySupplier($order);
        $supplierProducts = [];

        /** @var OrderProduct $orderProduct */
        foreach ($orderProducts as $orderProduct) {
            $supplierProducts[$orderProduct->getProduct()->getSupplier()->getName()][] = $orderProduct;
        }

        return $this->render('admin/order/edit.html.twig', array(
            'row' => $order,
            'orderProducts' => $orderProducts,
            'supplierProducts' => $supplierProducts,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="backend_order_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, User $user)
    {
        $filter_form = $this->createDeleteForm($user);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_order_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Order $order)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_order_delete', array('id' => $order->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
