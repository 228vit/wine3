<?php

namespace App\Controller\Admin;

use App\Entity\Rating;
use App\Entity\Product;
use App\Entity\ProductRating;
use App\Filter\RatingFilter;
use App\Form\RatingType;
use App\Repository\RatingRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminRatingController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'rating';
    CONST ENTITY_NAME = 'Rating';
    CONST NS_ENTITY_NAME = 'App:Rating';

    /**
     * Lists all rating entities.
     *
     * @Route("backend/rating/index", name="backend_rating_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, RatingFilter::class,
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
                    'sorting_field' => 'rating.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'rating.name',
                    'sortable' => true,
                ],
            ]
        ));
    }

    /**
     * Displays a form to edit an existing rating entity.
     *
     * @Route("backend/rating/grab", name="backend_rating_grab", methods={"GET"})
     */
    public function grabRatings(RatingRepository $ratingRepository,
                                ProductRepository $productRepository,
                                ProductRatingRepository $productRatingRepository)
    {
        $products = $productRepository->findAll();
        $uniqueSorts = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $ratings = json_decode($product->getRatings(), true);

            if (JSON_ERROR_NONE === json_last_error() AND (is_array($ratings))) {
                foreach ($ratings as $ratingName => $value) {
                    if (strlen($ratingName) < 2) continue;

                    $rating = $ratingRepository->findOrCreateByName($ratingName, $this->em);
                    $uniqueSorts[$rating->getName()] = $rating->getName();
                    // make m-m relation
                    $productRating = $productRatingRepository->findOneBy([
                        'product' => $product,
                        'rating' => $rating
                    ]);

                    if (null === $productRating) {
                        $productRating = (new ProductRating())
                            ->setProduct($product)
                            ->setRating($rating);
                    }

                    $productRating->setValue(intval($value));
                    $this->em->persist($productRating);
                }
            }
        }

        $this->em->flush();

        // todo: save changes

        return new Response('<pre>' . print_r($uniqueSorts, true));
    }

    /**
     * Creates a new rating entity.
     *
     * @Route("backend/rating/new", name="backend_rating_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $rating = new Rating();
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($rating);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_rating_edit', array('id' => $rating->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'rating' => $rating,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a rating entity.
     *
     * @Route("backend/rating/{id}", name="backend_rating_show", methods={"GET"})
     */
    public function showAction(Rating $rating)
    {
        $deleteForm = $this->createDeleteForm($rating);

        return $this->render('admin/rating/show.html.twig', array(
            'rating' => $rating,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing rating entity.
     *
     * @Route("backend/rating/{id}/edit", name="backend_rating_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, Rating $rating, FileUploader $fileUploader)
    {
        $editForm = $this->createForm(RatingType::class, $rating);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $rating->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $rating->setFlagPic($fileName);
            }

            $this->em->persist($rating);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_rating_edit', array('id' => $rating->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($rating);

        return $this->render('admin/rating/edit.html.twig', array(
            'row' => $rating,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a rating entity.
     *
     * @Route("backend/rating/{id}", name="backend_rating_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Rating $rating, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($rating);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $rating->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $rating->setFlagPic($fileName);
            }


            $this->em->remove($rating);
            $this->em->flush($rating);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_rating_index');
    }

    /**
     * Creates a form to delete a rating entity.
     */
    private function createDeleteForm(Rating $rating) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_rating_delete', array('id' => $rating->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
