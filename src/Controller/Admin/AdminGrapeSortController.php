<?php

namespace App\Controller\Admin;

use App\Entity\GrapeSort;
use App\Entity\GrapeSortAlias;
use App\Entity\Product;
use App\Entity\ProductGrapeSort;
use App\Filter\FoodFilter;
use App\Filter\GrapeSortFilter;
use App\Form\GrapeSortJoinOtherType;
use App\Form\GrapeSortType;
use App\Repository\GrapeSortRepository;
use App\Repository\ProductGrapeSortRepository;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityRepository;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminGrapeSortController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'grape_sort';
    CONST ENTITY_NAME = 'GrapeSort';
    CONST NS_ENTITY_NAME = 'App:GrapeSort';

    /**
     * Lists all grapeSort entities.
     *
     * @Route("backend/grape_sort/index", name="backend_grape_sort_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, GrapeSortFilter::class,
            'name', 'ASC');

        return $this->render('admin/grape_sort/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'grape_sort.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'grape_sort.name',
                    'sortable' => true,
                ],
            ]
        ));
    }

    /**
     * Displays a form to edit an existing grapeSort entity.
     *
     * @Route("backend/grape_sort/grab", name="backend_grape_sort_grab", methods={"GET"})
     */
    public function grabGrapeSorts(GrapeSortRepository $grapeSortRepository,
                                   ProductRepository $productRepository,
                                   ProductGrapeSortRepository $productGrapeSortRepository)
    {
        $products = $productRepository->findAll();
        $uniqueSorts = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $grapeSorts = json_decode($product->getGrapeSort(), true);

            if (JSON_ERROR_NONE === json_last_error() AND (is_array($grapeSorts))) {
                foreach ($grapeSorts as $grapeSortName => $value) {
                    $grapeSortName = trim(preg_replace('/\s{2,}/', ' ', $grapeSortName));
                    if (strlen($grapeSortName) < 3) continue;

                    // strip double spaces

                    $grapeSort = $grapeSortRepository->findOrCreateByName($grapeSortName, $this->em);
                    $uniqueSorts[$grapeSort->getName()] = $grapeSort->getName();
                    // make m-m relation
                    $productGrapeSort = $productGrapeSortRepository->findOneBy([
                        'product' => $product,
                        'grapeSort' => $grapeSort
                    ]);

                    if (null === $productGrapeSort) {
                        $productGrapeSort = (new ProductGrapeSort())
                            ->setProduct($product)
                            ->setGrapeSort($grapeSort);
                    }

                    $productGrapeSort->setValue(intval($value));
                    $this->em->persist($productGrapeSort);
                }
            }
        }

        $this->em->flush();

        // todo: save changes

        return new Response('<pre>' . print_r($uniqueSorts, true));
    }

    /**
     * Creates a new grapeSort entity.
     *
     * @Route("backend/grape_sort/new", name="backend_grape_sort_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $grapeSort = new GrapeSort();
        $form = $this->createForm(GrapeSortType::class, $grapeSort);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($grapeSort);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_grape_sort_edit', array('id' => $grapeSort->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'grapeSort' => $grapeSort,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Creates a new grapeSort entity.
     *
     * @Route("backend/grape_sort/{id}/join", name="backend_grape_sort_join", methods={"GET","POST"})
     */
    public function joinAction(Request $request,
                               GrapeSort $grapeSort,
                               ProductGrapeSortRepository $productGrapeSortRepository,
                               GrapeSortRepository $grapeSortRepository)
    {

        $query = $grapeSortRepository->getQueryAllExceptMe($grapeSort);

        $form = $this->createFormBuilder()
            ->add('grapeSort', EntityType::class, array(
                'class' => GrapeSort::class,
                'query_builder' => $query,
                'expanded' => false,
                'label' => 'Заменить на сорт:',
                'placeholder' => 'Выберите сорт',
            ))
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys
            $parentId = isset($form->getData()['grapeSort']) ? $form->getData()['grapeSort'] : null;

            /** @var GrapeSort $parent */
            $parent = $grapeSortRepository->find($parentId);
            if (null === $parent) {
                $this->addFlash('warning', 'Неверно выбран сорт для привязки.');

                return $this->render('admin/grape_sort/join.html.twig', array(
                    'row' => $grapeSort,
                    'form' => $form->createView(),
                    'model' => self::MODEL,
                    'entity_name' => self::ENTITY_NAME,

                ));
            }

            // make new alias
            $alias = (new GrapeSortAlias())
                ->setName($grapeSort->getName())
                ->setParent($parent);

            $this->em->persist($alias);

            // replace product grape sorts aliases to parent

            // todo: get all products w/current sort, replace sort, mark as deleted
            $productGrapeSorts = $productGrapeSortRepository->findBy([
                'grapeSort' => $grapeSort,
            ]);

            /** @var ProductGrapeSort $productGrapeSort */
            foreach ($productGrapeSorts as $productGrapeSort) {
                $productGrapeSort->setGrapeSort($parent);
                $this->em->persist($productGrapeSort);
                $this->em->flush();
            }

            $grapeSort->setDescription('delete');
            $this->em->persist($grapeSort);
            $this->em->remove($grapeSort);
            $this->em->flush();

            $this->addFlash('success', 'Grape sort was joined with otherm and deleted!');

            return $this->redirectToRoute('backend_grape_sort_index');

        }

//        $form = $this->createForm(GrapeSortJoinOtherType::class, $grapeSort);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/grape_sort/join.html.twig', array(
            'row' => $grapeSort,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a grapeSort entity.
     *
     * @Route("backend/grape_sort/{id}", name="backend_grape_sort_show", methods={"GET"})
     */
    public function showAction(GrapeSort $grapeSort)
    {
        $deleteForm = $this->createDeleteForm($grapeSort);

        return $this->render('admin/grapeSort/show.html.twig', array(
            'grapeSort' => $grapeSort,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing grapeSort entity.
     *
     * @Route("backend/grape_sort/{id}/edit", name="backend_grape_sort_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, GrapeSort $grapeSort, FileUploader $fileUploader)
    {
        $editForm = $this->createForm(GrapeSortType::class, $grapeSort);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $grapeSort->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $grapeSort->setFlagPic($fileName);
            }

            $this->em->persist($grapeSort);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_grape_sort_edit', array('id' => $grapeSort->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($grapeSort);

        return $this->render('admin/grape_sort/edit.html.twig', array(
            'row' => $grapeSort,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a grapeSort entity.
     *
     * @Route("backend/grape_sort/{id}", name="backend_grape_sort_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, GrapeSort $grapeSort, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($grapeSort);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $grapeSort->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $grapeSort->setFlagPic($fileName);
            }


            $this->em->remove($grapeSort);
            $this->em->flush($grapeSort);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_grape_sort_index');
    }

    /**
     * Creates a form to delete a grapeSort entity.
     */
    private function createDeleteForm(GrapeSort $grapeSort) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_grape_sort_delete', array('id' => $grapeSort->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

}
