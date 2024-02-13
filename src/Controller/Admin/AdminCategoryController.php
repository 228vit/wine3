<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Filter\CategoryFilter;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminCategoryController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'category';
    CONST ENTITY_NAME = 'Category';
    CONST NS_ENTITY_NAME = 'App:Category';

    /**
     * Lists all category entities.
     *
     * @Route("backend/category/index", name="backend_category_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $filter_form_class = CategoryFilter::class;

        $session_filters = $session->get('admin-filters', false);

        $repository = $this->em->getRepository(self::NS_ENTITY_NAME);

        $this->filter_form = $this->createForm($filter_form_class, null, array(
            'action' => $this->generateUrl('backend_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        $model = self::MODEL;
        $filter_form = $this->filter_form;

        if (false !== $session_filters && count($session_filters) && isset($session_filters[$model])) {
            $this->current_filters = $session_filters[$model];
            $filter_form->submit($this->current_filters);

            $filterBuilder = $repository->createQueryBuilder($model);

            $this->query_builder_updater
                ->addFilterConditions($filter_form, $filterBuilder)
                ->orderBy($model.'.id', 'asc')
            ;

            $query = $filterBuilder->getQuery();
        } else {
            $this->current_filters = null;
            // default query w/sorting
            $query = $repository->createQueryBuilder($model)
                ->orderBy($model.'.id', 'asc')
                ->getQuery();
        }

        $pagination = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            self::ROWS_PER_PAGE  /*limit per page*/
        );

        return $this->render('admin/category/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'category.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'forTree',
                    'sorting_field' => 'category.name',
                    'sortable' => true,
                ],
                'a.slug' => [
                    'title' => 'Slug',
                    'row_field' => 'slug',
                    'sorting_field' => 'category.slug',
                    'sortable' => false,
                ],
                'a.isActive' => [
                    'title' => 'Is active?',
                    'row_field' => 'isActive',
                    'sorting_field' => 'category.isActive',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new category entity.
     *
     * @Route("backend/category/new", name="backend_category_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_category_edit', array('id' => $category->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'category' => $category,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a category entity.
     *
     * @Route("backend/category/{id}", name="backend_category_show", methods={"GET"})
     */
    public function showAction(Category $category)
    {
        $deleteForm = $this->createDeleteForm($category);

        return $this->render('admin/category/show.html.twig', array(
            'category' => $category,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing category entity.
     *
     * @Route("backend/category/{id}/edit", name="backend_category_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, Category $category)
    {
        $editForm = $this->createForm(CategoryType::class, $category);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $this->em->persist($category);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_category_edit', array('id' => $category->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($category);

        return $this->render('admin/category/edit.html.twig', array(
            'row' => $category,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a category entity.
     *
     * @Route("backend/category/{id}", name="backend_category_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Category $category)
    {
        $filter_form = $this->createDeleteForm($category);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush($category);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_category_index');
    }

    /**
     * Creates a form to delete a category entity.
     */
    private function createDeleteForm(Category $category) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_category_delete', array('id' => $category->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
