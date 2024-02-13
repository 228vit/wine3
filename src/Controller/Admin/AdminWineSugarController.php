<?php

namespace App\Controller\Admin;

use App\Entity\WineSugar;
use App\Filter\WineSugarFilter;
use App\Form\WineSugarType;
use App\Repository\WineSugarRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class AdminWineSugarController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'wine_sugar';
    CONST ENTITY_NAME = 'WineSugar';
    CONST NS_ENTITY_NAME = 'App:WineSugar';

    /**
     * Lists all wineSugar entities.
     *
     * @Route("backend/wine_sugar/index", name="backend_wine_sugar_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, WineSugarFilter::class, 'id', 'ASC');

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
                    'sorting_field' => 'wine_sugar.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'wine_sugar.name',
                    'sortable' => true,
                ],
                'a.description' => [
                    'title' => 'Description',
                    'row_field' => 'description',
                    'sortable' => false,
                ],
                'a.aliasesAsString' => [
                    'title' => 'Aliases',
                    'row_field' => 'aliasesAsString',
                    'sortable' => false,
                ],
            ]
        ));
    }


    /**
     * Creates a new wineSugar entity.
     *
     * @Route("backend/wine_sugar/new", name="backend_wine_sugar_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, WineSugarRepository $repository, EntityManagerInterface $em)
    {
        $wineSugar = new WineSugar();
        $form = $this->createForm('App\Form\WineSugarType', $wineSugar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($wineSugar);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_wine_sugar_edit', array('id' => $wineSugar->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $wineSugar,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(WineSugar $wineSugar, WineSugarRepository $repository)
    {
        $slug = $wineSugar->getSlug() ?? Slugger::urlSlug($wineSugar->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a wineSugar entity.
     *
     * @Route("backend/wine_sugar/{id}", name="backend_wine_sugar_show", methods={"GET"})
     */
    public function showAction(WineSugar $wineSugar)
    {
        $deleteForm = $this->createDeleteForm($wineSugar);

        return $this->render('admin/wineSugar/show.html.twig', array(
            'wineSugar' => $wineSugar,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing wineSugar entity.
     *
     * @Route("backend/wine_sugar/{id}/edit", name="backend_wine_sugar_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, WineSugar $wineSugar, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($wineSugar);
        $editForm = $this->createForm(WineSugarType::class, $wineSugar);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_wine_sugar_edit', array('id' => $wineSugar->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/wine_sugar/edit.html.twig', array(
            'row' => $wineSugar,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a wineSugar entity.
     *
     * @Route("backend/wine_sugar/{id}", name="backend_wine_sugar_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, WineSugar $wineSugar)
    {
        $filter_form = $this->createDeleteForm($wineSugar);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($wineSugar);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_wine_sugar_index');
    }

    /**
     * Creates a form to delete a wineSugar entity.
     *
     * @param WineSugar $wineSugar The wineSugar entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(WineSugar $wineSugar)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_wine_sugar_delete', array('id' => $wineSugar->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
