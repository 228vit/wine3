<?php

namespace App\Controller\Admin;

use App\Entity\Food;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\ProductGrapeSort;
use App\Entity\ProductRating;
use App\Filter\OfferFilter;
use App\Form\OfferType;
use App\Form\ProductType;
use App\Repository\GrapeSortAliasRepository;
use App\Repository\GrapeSortRepository;
use App\Repository\OfferRepository;
use App\Repository\ProductGrapeSortRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;
use App\Repository\RatingRepository;
use App\Service\FileUploader;
use App\Service\WineColorService;
use App\Service\WineSugarService;
use App\Utils\Slugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminOfferController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'offer';
    CONST ENTITY_NAME = 'Offer';
    CONST NS_ENTITY_NAME = 'App:Offer';

    /**
     * Lists all offer entities.
     *
     * @Route("backend/offer/index", name="backend_offer_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, OfferFilter::class, 'id', 'DESC');

        return $this->render('admin/offer/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'current_filters_string' => $this->current_filters_string,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'offer.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'offer.name',
                    'sortable' => true,
                ],
                'a.shortSummary' => [
                    'title' => '-',
                    'row_field' => 'name',
                    'sorting_field' => 'offer.name',
                    'sortable' => true,
                ],
                'a.price' => [
                    'title' => 'Price',
                    'row_field' => 'price',
                    'sorting_field' => 'offer.price',
                    'sortable' => true,
                ],
                'a.isActive' => [
                    'title' => 'Is active?',
                    'row_field' => 'isActive',
                    'sorting_field' => 'offer.isActive',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Displays a form to edit an existing offer entity.
     *
     * @Route("backend/offer/{id}/edit", name="backend_offer_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request,
                               Offer $offer,
                               OfferRepository $offerRepository
    )
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (empty($offer->getSlug())) {
                $offer->setSlug($this->makeSlug($offer, $offerRepository));
            }

            $grapeSorts = $request->request->get('grapeSort', []);
            $res = [];
            foreach ($grapeSorts as $grapeSort) {
                $res[$grapeSort['name']] = $grapeSort['value'];
            }
            $offer->setGrapeSort(json_encode($res));

            $ratings = $request->request->get('rating', []);
            $res = [];
            foreach ($ratings as $rating) {
                $res[$rating['name']] = $rating['value'];
            }
            $offer->setRatings(json_encode($res));

            $this->em->persist($offer);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_offer_edit', array('id' => $offer->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        // todo: create form again?
        $form = $this->createForm(OfferType::class, $offer);

        $deleteForm = $this->createDeleteForm($offer);

        return $this->render('admin/offer/edit.html.twig', array(
            'row' => $offer,
            'ratings' => json_decode($offer->getRatings(), true),
            'grapeSorts' => json_decode($offer->getGrapeSort(), true),
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }


    /**
     * @Route("backend/offer/fix_ratings", name="backend_offer_fix_ratings", methods={"GET"})
     */
    public function fixRatings(OfferRepository $offerRepository)
    {
        $offers = $offerRepository->findAll();
        foreach ($offers as $offer) {
            $ratings = $offer->getRatings();
            if (empty($ratings)) continue;

            json_decode($ratings);
            if (JSON_ERROR_NONE === json_last_error() OR empty($ratings)) {
                continue;
            }

            // превратим RP: 92, WS: 93, ST: 92, W&S: 90, GP: 92 - в массив
            $parts = explode(',', $ratings);
            $result = [];

            foreach ($parts as $part) {
                $rateArr = explode(':', $part);
                if (2 !== count($rateArr)) { continue; }

                $result[trim($rateArr[0])] = trim($rateArr[1]);
            }

            if (0 !== count($result)) {
                $offer->setRatings(json_encode($result));
            }

            $this->em->persist($offer);
        }
        $this->em->flush();

        return new Response('ok');
    }

    /**
     * @Route("backend/offer/fix_grape", name="backend_offer_fix_grape", methods={"GET"})
     */
    public function fixGrape(OfferRepository $offerRepository)
    {
        $offers = $offerRepository->findAll();
        foreach ($offers as $offer) {
            $grapeSort = $offer->getGrapeSort();
            $result = [];

            if (empty($grapeSort)) continue;

            $grapeSorts = json_decode($grapeSort);
            if (JSON_ERROR_NONE === json_last_error() AND (is_array($grapeSorts))) {
                foreach ($grapeSorts as $sort) {
                    if (is_array($sort)) {
                        $sort = $sort[0];
                    }

                    $sort = trim(str_replace('|', ' ', $sort));
                    preg_replace('/(\s){2,}/', ' ', $sort);

                    preg_match('/(.+)\s(\d{1,})?/i', $sort, $parts);
                    if (3 == count($parts)) {
                        array_shift($parts);
                        $result[trim($parts[0])] = intval($parts[1]);
                    } else if (2 == count($parts)) {
                        $result[trim($parts[0])] = 0;
                    }
                }
                // todo: save JSON
                if (0 !== count($result)) {
                    $offer->setGrapeSort(json_encode($result));
                    $this->em->persist($offer);
                    continue;
                }

            }

            // превратим Каберне Совиньон  80%,Мерло  20% - в массив
            $grapeSorts = explode(',', $grapeSort);

            foreach ($grapeSorts as $sort) {
                $sort = str_replace('|', ' ', $sort);
                preg_replace('/(\s){2,}/', ' ', $sort);

                preg_match('/(.+)\s(\d{1,}%)?/i', $sort, $parts);
                if (3 == count($parts)) {
                    array_shift($parts);
                    $result[trim($parts[0])] = intval($parts[1]);
                }
            }

            if (0 !== count($result)) {
                $offer->setGrapeSort(json_encode($result));
                $this->em->persist($offer);
            }

        }
        $this->em->flush();

        return new Response('ok');
    }

    /**
     * @Route("backend/offer/{id}", name="backend_offer_show", methods={"GET"})
     */
    public function showAction(Offer $offer)
    {
        $deleteForm = $this->createDeleteForm($offer);

        return $this->render('admin/offer/show.html.twig', array(
            'offer' => $offer,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("backend/offer/{id}/link", name="backend_offer_link", methods={"GET", "POST"})
     */
    public function linkAction(Offer $offer,
                               Request $request,
                               OfferRepository $offerRepository,
                               ProductRepository $productRepository)
    {
        $nameChunks = explode(' ', $offer->getName());
        $products = $productRepository->searchProducts($nameChunks, 10);

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (empty($offer->getSlug())) {
                $offer->setSlug($this->makeSlug($offer, $offerRepository));
            }

            $grapeSorts = $request->request->get('grapeSort', []);
            $res = [];
            foreach ($grapeSorts as $grapeSort) {
                $res[$grapeSort['name']] = $grapeSort['value'];
            }
            $offer->setGrapeSort(json_encode($res));

            $ratings = $request->request->get('rating', []);
            $res = [];
            foreach ($ratings as $rating) {
                $res[$rating['name']] = $rating['value'];
            }
            $offer->setRatings(json_encode($res));

            $this->em->persist($offer);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_offer_link', array('id' => $offer->getId()));
        }

        return $this->render('admin/offer/link.html.twig', array(
            'row' => $offer,
            'ratings' => json_decode($offer->getRatings(), true),
            'grapeSorts' => json_decode($offer->getGrapeSort(), true),
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'products' => $products,
        ));
    }

    /**
     * @Route("backend/offer/{id}/product", name="backend_offer_to_product", methods={"GET"})
     */
    public function makeProductAction(Offer $offer,
                                      GrapeSortRepository $grapeSortRepository,
                                      GrapeSortAliasRepository $grapeSortAliasRepository,
                                      ProductGrapeSortRepository $productGrapeSortRepository,
                                      RatingRepository $ratingRepository,
                                      ProductRatingRepository $productRatingRepository,
                                      WineColorService $wineColorService,
                                      WineSugarService $wineSugarService,
                                      SessionInterface $session,
                                      FileUploader $fileUploader)
    {
        $session->set('offer_id', $offer->getId());


        $product = (new Product())
            ->setName($offer->getName())
            ->setContent($offer->getDescription())
            ->setVendor($offer->getVendor())
            ->setCategory($offer->getCategory())
            ->setCountry($offer->getCountry())
            ->setRegion($offer->getRegion())
            ->setName($offer->getName())
            ->setSlug($offer->getSlug())
//            ->setProductCode($offer->getProductCode())
            ->setPrice($offer->getPrice())
            ->setPriceStatus($offer->getPriceStatus())
            ->setPacking($offer->getPacking())
            // wine color
            ->setColor($offer->getColor())
            ->setWineColor($wineColorService->getWineColor($offer->getColor()))
            // wine sugar
            ->setType($offer->getType())
            ->setWineSugar($wineSugarService->getWineSugar($offer->getType()))

            ->setAlcohol($offer->getAlcohol())
            ->setGrapeSort($offer->getGrapeSort())
            ->setRatings($offer->getRatings())
            ->setYear($offer->getYear())
            ->setVolume($offer->getVolume())
            ->setServeTemperature($offer->getServeTemperature())
            ->setDecantation($offer->getDecantation())
            ->setAppellation($offer->getAppellation())
            ->setPacking($offer->getPacking())
            ->setFermentation($offer->getFermentation())
            ->setAging($offer->getAging())
            ->setAgingType($offer->getAgingType())
        ;

        if ($offer->getPicUrl()) {
            $picPathRelative = $fileUploader->makePng(
                $offer->getPicUrl(),
                $offer->getImportYml() ? $offer->getImportYml()->getRotatePicAngle() : 0
            );
            if ($picPathRelative) {
                $product
                    ->setContentPic($picPathRelative)
                    ->setAnnouncePic($picPathRelative)
                ;
            }
        }

        /** @var Food $food */
        foreach ($offer->getFoods() as $food) {
            $product->addFood($food);
        }
        
        // todo: loop over grape sorts
        $grapeSorts = json_decode($offer->getGrapeSort(), true);

        if (JSON_ERROR_NONE === json_last_error() AND (is_array($grapeSorts))) {
            foreach ($grapeSorts as $grapeSortName => $value) {
                // strip double spaces
                $grapeSortName = trim(preg_replace('/\s{2,}/', ' ', $grapeSortName));

                if (strlen($grapeSortName) < 3) continue;

                // get by alias
                $alias = $grapeSortAliasRepository->findOneBy(['name' => $grapeSortName]);

                // todo: test it!!!
                if (null !== $alias) {
                    $grapeSort = $alias->getParent();
                } else {
                    // get by name
                    $grapeSort = $grapeSortRepository->findOrCreateByName($grapeSortName, $this->em);
                }

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
                $product->addProductGrapeSort($productGrapeSort);
            }
        }

        // todo: loop over ratings
        $ratings = json_decode($offer->getRatings(), true);

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
                $product->addProductRating($productRating);
            }
        }

        $this->addFlash('warning', 'Оффер скопирован в карточку товара. Проверьте и сохраните карточку товара.');

        $form = $this->createForm(ProductType::class, $product);

        return $this->render('admin/product/new.html.twig', array(
            'row' => $product,
            'form' => $form->createView(),
            'model' => AdminProductController::MODEL,
            'entity_name' => AdminProductController::ENTITY_NAME,
        ));

    }

    /**
     * @Route("backend/offer/ajax/price", name="ajax_offer_get_price", methods={"GET"})
     */
    public function ajaxGetOfferPriceAction(Request $request)
    {
        $id = $request->query->get('id', false);
        $em = $this->getDoctrine()->getManager();
        $offer = $em->getRepository(Offer::class)->find($id);

        if (false === $offer) {
            return new JsonResponse('Wrong ID', 400);
        }

        return new JsonResponse([
            'price' => $offer->getPrice(),
        ], 200);
    }

    private function makeSlug(Offer $offer, OfferRepository $offerRepository)
    {
        $slug = Slugger::urlSlug($offer->getName(), array('transliterate' => true));

        while($offerRepository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Deletes a offer entity.
     *
     * @Route("backend/offer/{id}/delete", name="backend_offer_delete", methods={"DELETE", "GET"})
     */
    public function delete(Request $request, Offer $offer)
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $this->em->remove($offer);
            $this->em->flush($offer);

            $this->addFlash('success', 'Record was successfully deleted!');
            return $this->redirectToRoute('backend_offer_index');
        }

        $filter_form = $this->createDeleteForm($offer);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($offer);
            $em->flush($offer);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_offer_index');
    }

    /**
     * Creates a form to delete a offer entity.
     *
     * @param Offer $offer The offer entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Offer $offer)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_offer_delete', array('id' => $offer->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
