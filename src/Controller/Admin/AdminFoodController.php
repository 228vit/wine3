<?php

namespace App\Controller\Admin;

use App\Entity\Food;
use App\Filter\FoodFilter;
use App\Filter\OfferFilter;
use App\Form\FoodType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminFoodController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'food';
    CONST ENTITY_NAME = 'Food';
    CONST NS_ENTITY_NAME = 'App:Food';

    /**
     * Lists all food entities.
     *
     * @Route("backend/food/index", name="backend_food_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, FoodFilter::class,
            'name', 'ASC');

        return $this->render('admin/common/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'food.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'food.name',
                    'sortable' => true,
                ],
            ]
        ));
    }

    /**
     * Creates a new food entity.
     *
     * @Route("backend/food/new", name="backend_food_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $food = new Food();
        $form = $this->createForm(FoodType::class, $food);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($food);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_food_edit', array('id' => $food->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'food' => $food,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a food entity.
     *
     * @Route("backend/food/{id}", name="backend_food_show", methods={"GET"})
     */
    public function showAction(Food $food)
    {
        $deleteForm = $this->createDeleteForm($food);

        return $this->render('admin/food/show.html.twig', array(
            'food' => $food,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing food entity.
     *
     * @Route("backend/food/{id}/edit", name="backend_food_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, Food $food)
    {
        $editForm = $this->createForm(FoodType::class, $food);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->persist($food);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_food_edit', array('id' => $food->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($food);

        return $this->render('admin/common/edit.html.twig', array(
            'row' => $food,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a food entity.
     *
     * @Route("backend/food/{id}", name="backend_food_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Food $food)
    {
        $filter_form = $this->createDeleteForm($food);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($food);
            $em->flush($food);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_food_index');
    }

    /**
     * Creates a form to delete a food entity.
     */
    private function createDeleteForm(Food $food) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_food_delete', array('id' => $food->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
