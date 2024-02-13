<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Filter\PageFilter;
use App\Form\PageType;
use App\Repository\PageRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminPageController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'page';
    CONST ENTITY_NAME = 'Page';
    CONST NS_ENTITY_NAME = 'App:Page';

    /**
     * Lists all Page entities.
     *
     * @Route("backend/page/index", name="backend_page_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, PageFilter::class);

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
                    'sorting_field' => 'Page.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'Page.name',
                    'sortable' => true,
                ],
                'a.slug' => [
                    'title' => 'Slug',
                    'row_field' => 'slug',
                    'sorting_field' => 'Page.slug',
                    'sortable' => false,
                ],
            ]
        ));
    }


    /**
     * Creates a new Page entity.
     *
     * @Route("backend/page/new", name="backend_page_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, PageRepository $repository, EntityManagerInterface $em)
    {
        $page = new Page();
        $form = $this->createForm('App\Form\PageType', $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
//            $page->setSlug($this->makeSlug($page, $repository));

            $em->persist($page);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_page_edit', array('id' => $page->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $page,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Page $page, PageRepository $repository)
    {
        $slug = $page->getSlug() ?? Slugger::urlSlug($page->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a Page entity.
     *
     * @Route("backend/page/{id}", name="backend_page_show", methods={"GET"})
     */
    public function showAction(Page $page)
    {
        $deleteForm = $this->createDeleteForm($page);

        return $this->render('admin/page/show.html.twig', array(
            'Page' => $page,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     * @Route("backend/page/{id}/edit", name="backend_page_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Page $page, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($page);
        $editForm = $this->createForm(PageType::class, $page);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var UploadedFile $file */
            $file = $page->getPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $page->setPic($fileName);
            }

            $this->em->persist($page);
            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_page_edit', array('id' => $page->getId()));
        }
        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/page/edit.html.twig', array(
            'row' => $page,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a Page entity.
     *
     * @Route("backend/page/{id}", name="backend_page_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Page $page)
    {
        $filter_form = $this->createDeleteForm($page);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($page);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_page_index');
    }

    /**
     * Creates a form to delete a Page entity.
     *
     * @param Page $page The Page entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Page $page)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_page_delete', array('id' => $page->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
