<?php

namespace App\Controller\Front;

use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Entity\GrapeSort;
use App\Entity\Product;
use App\Entity\WineCard;
use App\Entity\WineColor;
use App\Entity\WineSugar;
use App\Filter\Front\FrontProductFilter;
use App\Repository\CountryRepository;
use App\Repository\GrapeSortRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\WineCardRepository;
use App\Repository\WineColorRepository;
use App\Repository\WineSugarRepository;
use App\Service\ProductDataService;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    use FrontTraitController;

    CONST ROWS_PER_PAGE = 20;
    CONST MODEL = 'product';
    CONST ENTITY_NAME = 'Product';
    CONST NS_ENTITY_NAME = 'App:Product';
    private $currentFilters = [];

    /**
     * @Route("/cabinet/product", name="cabinet_product_index")
     */
    public function indexAction(Request $request,
                                SessionInterface $session,
                                WineCardRepository $wineCardRepository,
                                ProductDataService $productDataService)
    {
//        $wineCards = $wineCardRepository->getAllByUser($this->getUser());
        $currentWineCard = $wineCardRepository->find($session->get('currentWineCard', 0));
        $session_order_field = $session->get('order_field', 'name');
        $orderMapping = [
            'name' => 'По названию',
            'price' => 'По цене',
            'country' => 'По стране',
        ];

//        if (null === $currentWineCard AND 0 !== count($wineCards)) {
//            $currentWineCard = $wineCards[0];
//            $session->set('currentWineCard', $currentWineCard->getId());
//        }

        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);

        return $this->render('front/catalog/index.html.twig', array(
            'pagination' => $pagination,
            'currentWineCard' => $currentWineCard,
//            'wineCards' => $wineCards,
            'isAjax' => $request->isXmlHttpRequest(),
            'totalRows' => $pagination->getTotalItemCount(),
            'current_filters' => $this->current_filters,
            'currentFilters' => $this->currentFilters,
            'current_filters_string' => $this->current_filters_string,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'orderField' => $session_order_field,
            'orderMapping' => $orderMapping,
        ));
    }

    /**
     * @Route("/cabinet/product/set_order", name="cabinet_product_set_order")
     */
    public function setOrderAction(Request $request,
                                SessionInterface $session)
    {
        $session_order_field = $request->get('field', 'name');
        $session->set('order_field', $session_order_field);

        return $this->redirectToRoute('cabinet_product_index');
    }

    /**
     * @Route("/cabinet/product/change_wine_card", name="cabinet_product_change_wine_card")
     */
    public function changeWineCardAction(Request $request,
                                         SessionInterface $session,
                                         WineCardRepository $wineCardRepository)
    {
        $wineCardId = $request->query->get('id', null);
        $wineCard = $wineCardRepository->find($wineCardId);
        if (null === $wineCard) {
            return new JsonResponse([], 404);
        }

        $session->set('currentWineCard', $wineCard->getId());

        return $this->redirectToRoute('cabinet_product_index');
    }

    /**
     * Save filter values in session.
     *
     * @Route("/apply/filter/product", name="ajax_apply_filter_product", methods={"POST"})
     */
    public function ajaxApplyFilter(Request $request,
                                    SessionInterface $session,
                                    ProductDataService $productDataService)
    {

        $filters = $request->request->get('product_filter');
        // unset empty values
        if (is_array($filters)) {
            $filters = array_filter($filters, function($value) { return $value !== ''; });
            unset($filters['_token']);
        }

        $session->set('filters', array(
            self::MODEL => $filters,
        ));

        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);

        return new JsonResponse(['totalFilteredProducts' => $pagination->getTotalItemCount()]);
    }

    /**
     * Save filter values in session.
     *
     * @Route("/cabinet/save/filter/product", name="cabinet_save_filter_product", methods={"GET", "POST"})
     */
    public function saveProductFilter(Request $request, SessionInterface $session, ProductDataService $productDataService)
    {
        $filters = $request->request->get('product_filter');
        // unset empty values
        if (is_array($filters)) {
            $filters = array_filter($filters, function($value) { return $value !== ''; });
            unset($filters['_token']);
        }
        $session->set('filters', array(
            self::MODEL => $filters,
        ));

        return $this->redirectToRoute('front_catalog');
//        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);
//
//        return new JsonResponse(['totalFilteredProducts' => $pagination->getTotalItemCount()]);
    }

    /**
     * @Route("/cabinet/product/filters", name="cabinet_product_filters")
     */
    public function renderFilters(Request $request,
                                  SessionInterface $session,
                                  SupplierRepository $supplierRepository,
                                  WineColorRepository $wineColorRepository,
                                  WineSugarRepository $wineSugarRepository,
                                  GrapeSortRepository $grapeSortRepository,
                                  CountryRepository $countryRepository,
                                  ProductDataService $productDataService)
    {
        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);

        $sessionFilters = $session->get('filters', []);
//        dd($sessionFilters);
        $sessionFilters['product'] = $sessionFilters['product'] ?? [];
        $sessionFilters['product']['grapeSort'] = $sessionFilters['product']['grapeSort'] ?? [];
        $sessionFilters['product']['wineColor'] = $sessionFilters['product']['wineColor'] ?? [];
        $sessionFilters['product']['wineSugar'] = $sessionFilters['product']['wineSugar'] ?? [];
        $sessionFilters['product']['supplier'] = $sessionFilters['product']['supplier'] ?? [];
        $sessionFilters['product']['country'] = $sessionFilters['product']['country'] ?? [];
        $sessionFilters['product']['vendor'] = $sessionFilters['product']['vendor'] ?? [];
        $sessionFilters['product']['volume'] = $sessionFilters['product']['volume'] ?? [];
        $sessionFilters['product']['alcohol'] = $sessionFilters['product']['alcohol'] ?? [];
        $sessionFilters['product']['year'] = $sessionFilters['product']['year'] ?? '';
        $sessionFilters['product']['years'] = $sessionFilters['product']['years'] ?? '';
        $sessionFilters['product']['price_from'] = $sessionFilters['product']['price_from'] ?? '';
        $sessionFilters['product']['price_to'] = $sessionFilters['product']['price_to'] ?? '';
        $productFilters = null !== $sessionFilters AND isset($sessionFilters['product']) ?
            $sessionFilters['product'] : [];

        $suppliers = $supplierRepository->findBy([], ['name' => 'ASC']);
        $wineColors = $wineColorRepository->findAll();
        $wineSugars = $wineSugarRepository->findAll();
        $countries = $countryRepository->findBy([], ['name' => 'ASC']);
        $grapeSorts = $grapeSortRepository->findBy([], ['name' => 'ASC']);

        return $this->render('front/product/filters.html.twig', array(
            'totalRows' => $pagination->getTotalItemCount(),
            'sessionFilters' => $sessionFilters,
            'productFilters' => $productFilters,
            'suppliers' => $suppliers,
            'wineColors' => $wineColors,
            'wineSugars' => $wineSugars,
            'countries' => $countries,
            'grapeSorts' => $grapeSorts,
            'current_filters' => $this->current_filters,
            'currentFilters' => $this->currentFilters,
            'current_filters_string' => $this->current_filters_string,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * @Route("/cabinet/product/{id}/show", name="cabinet_product_show")
     */
    public function show(Request $request, Product $product, WineCardRepository $repository)
    {
        $wineCards = $repository->getAllByUser($this->getUser());

        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'front/product/show_ajax.html.twig' : 'front/product/show.html.twig';

        return $this->render($template, array(
            'row' => $product,
            'wineCards' => $wineCards,
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function getPagination(Request $request,
                                   SessionInterface $session,
                                   ProductDataService $productDataService,
                                   string $filter_form_class)
    {
        $this->filter_form = $this->createForm($filter_form_class, null, array(
            'action' => $this->generateUrl('cabinet_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        /** @var Query $query */
        $query = $this->buildQuery($session, $productDataService, $this->filter_form, self::MODEL);

        $pagination = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            self::ROWS_PER_PAGE  /*limit per page*/
        );

        return $pagination;
    }

    private function buildQuery(SessionInterface $session,
                                ProductDataService $productDataService,
                                FormInterface $filter_form,
                                string $model)
    {
//        $sort_by = $request->query->get('sort_by', 'id');
//        $order_direction = $request->query->get('order', 'asc');
        $session_filters = $session->get('filters', false);
        $session_order_field = $session->get('order_field', 'name');
        $session_order_direction = $session->get('order_direction', 'asc');

        if (false !== $session_filters && count($session_filters) && isset($session_filters[$model])) {
            $this->current_filters = $session_filters[$model];
            $filter_form->submit($this->current_filters);

            $query = $this->productRepository->getJoinedQuery($model);
            $query->orderBy($model.'.'.$session_order_field, $session_order_direction);

            foreach ($session_filters[$model] as $filter => $value) {
                switch ($filter){
                    case 'name':
                        $this->currentFilters[$filter][] = [
                            'name' => $value,
                            'value' => $filter,
                        ];
                        $query->andWhere($model.'.name LIKE :name')->setParameter('name', "%$value%");
                        break;
                    case 'country':
                        $countries = $this->countryRepository->findAllByIds($value);
                        /** @var Country $country */
                        foreach ($countries as $country) {
                            $this->currentFilters[$filter][] = [
                                'name' => $country->getName(),
                                'value' => $country->getId(),
                            ];
                        }

                        $query->andWhere($model.'.country IN (:country)')->setParameter('country', $value);
                        break;
                    case 'region':
                        $regions = $this->regionRepository->findWithWines();
                        /** @var CountryRegion $region */
                        foreach ($regions as $region) {
                            $this->currentFilters[$filter][] = [
                                'name' => $region->getName(),
                                'value' => $region->getId(),
                            ];
                        }
                        $query->andWhere($model.'.region IN (:region)')->setParameter('region', $value);
                        break;
                    case 'grapeSort':
                        $grapeSorts = $this->grapeSortRepository->findAllByIds($value);
                        /** @var GrapeSort $grapeSort */
                        foreach ($grapeSorts as $grapeSort) {
                            $this->currentFilters[$filter][] = [
                                'name' => $grapeSort->getName(),
                                'value' => $grapeSort->getId(),
                            ];
                        }

                        $productIds = [];
                        /** @var Product $product */
                        foreach ($this->productRepository->findByGrapeSorts($grapeSorts) as $product) {
                            $productIds[] = $product->getId();
                        }

                        if (count($productIds)) {
                            $query->andWhere($model.'.id IN (:productIds)')
                                ->setParameter('productIds', $productIds);
                        }
                        break;
                    case 'alcohol':
                        $query->andWhere($model.'.alcohol IN (:alcohol)')->setParameter('alcohol', $value);
                        break;
                    case 'worldPart':
                        $country_ids = [];
                        foreach ($value as $wp) {
                            $countries = $this->countryRepository->findBy(['worldPart' => $value]);
                            $this->currentFilters[$filter][] = [
                                'name' => Country::WORLD_PARTS[$wp],
                                'value' => $wp,
                            ];

                            /** @var Country $country */
                            foreach ($countries as $country) {
                                $country_ids[] = $country->getId();
                            }
                        }

                        $query->andWhere($model.'.country IN (:country_ids)')->setParameter('country_ids', $country_ids);

                        break;
                    case 'vendor':
                        $query->andWhere($model.'.vendor IN (:vendor)')->setParameter('vendor', $value);
                        break;
                    case 'volume':
                        $bottleValues = $productDataService->getBottleVolumesReversed();
                        foreach ($value as $bottleValue) {
                            $this->currentFilters[$filter][] = [
                                'name' => 'объем '.$bottleValue.'л.',
                                'value' => $bottleValues[$bottleValue],
                            ];
                        }
                        $query->andWhere($model.'.volume IN (:volume)')->setParameter('volume', $value);
                        break;
                    case 'supplier':
                        $query->andWhere($model.'.supplier IN (:supplier)')->setParameter('supplier', $value);
                        break;
                    case 'wineSugar':
                        $rows = $this->wineSugarRepository->findAllByIds($value);
                        /** @var WineSugar $row */
                        foreach ($rows as $row) {
                            $this->currentFilters[$filter][] = [
                                'name' => $row->getName(),
                                'value' => $row->getId(),
                            ];
                        }
                        $query->andWhere($model.'.wineSugar IN (:wineSugar)')->setParameter('wineSugar', $value);
                        break;
                    case 'wineColor':
                        $rows = $this->wineColorRepository->findAllByIds($value);
                        /** @var WineColor $row */
                        foreach ($rows as $row) {
                            $this->currentFilters[$filter][] = [
                                'name' => $row->getName(),
                                'value' => $row->getId(),
                            ];
                        }
                        $query->andWhere($model.'.wineColor IN (:wineColors)')->setParameter('wineColors', $value);
                        break;
                    case 'years':
                        $query->andWhere($model.'.year IN (:years)')->setParameter('years', $value);
                        break;

                    case 'year':
                        $value = intval($value);
                        $query->andWhere($model.'.year  = :year')->setParameter('year', $value);
                        $this->currentFilters[$filter][] = [
                            'name' => 'год: ' . $value,
                            'value' => 'name',
                        ];
                        break;

                    case 'price_from':
                        $value = intval($value);
                        $query->andWhere($model.'.price >= :price_from')->setParameter('price_from', $value);
                        $this->currentFilters[$filter][] = [
                            'name' => 'цена от: ' . $value,
                            'value' => 'name',
                        ];
                        break;

                    case 'price_to':
                        $value = intval($value);
                        $query->andWhere($model.'.price <= :price_to')->setParameter('price_to', $value);
                        $this->currentFilters[$filter][] = [
                            'name' => 'цена до: ' . $value,
                            'value' => 'name',
                        ];
                        break;

                    default:
                        $this->currentFilters[$filter][] = [
                            'name' => $filter,
                            'value' => $value,
                        ];
                        $query->andWhere("$model.$filter = :$filter")->setParameter($filter, $value);
                        $this->current_filters_string[$filter] = $value;
                }
            }

        } else {
            $this->current_filters = null;
            // default query w/sorting
            $query = $this->productRepository->getJoinedQuery($model)
                ->orderBy($model.'.'.$session_order_field, $session_order_direction)
                ->getQuery()
            ;
        }

//        dd($this->currentFilters);
        return $query;
    }

    /**
     * @Route("cabinet/reset/product_filter", name="cabinet_reset_product_filter")
     */
    public function resetModelFilter(Request $request, SessionInterface $session)
    {
        $current_filters = $session->get('filters', false);
        if (isset($current_filters[self::MODEL])) {
            unset($current_filters[self::MODEL]);
        }

        $session->set('filters', $current_filters);

        return $this->redirectToRoute('cabinet_product_index');
    }

    /**
     * @Route("cabinet/user/winecards", name="ajax_user_winecards", methods={"GET"})
     */
    public function ajaxUserWineCards(Request $request,
                                      ProductRepository $productRepository,
                                      WineCardRepository $wineCardRepository)
    {
        $product = $productRepository->findOneBy([
            'id' => $request->query->get('product_id', null)
        ]);

        if (null === $product) {
            throw new NotFoundHttpException();
        }

        $wineCards = $wineCardRepository->getAllByUser($this->getUser());

        return $this->render('front/product/productWineCards.html.twig', [
            'wineCards' => $wineCards,
            'product' => $product,
        ]);
    }

    /**
     * @Route("cabinet/product/winecard/link", name="ajax_product_winecard", methods={"GET"})
     */
    public function ajaxLinkProductToWinecard(Request $request,
                                              SessionInterface $session,
                                              WineCardRepository $wineCardRepository)
    {
        $p_id = $request->query->get('product_id', false);
        $wc_id = $request->query->get('winecard_id', false);
        $checked = boolval($request->query->get('checked', false));
        $currentWineCard = $wineCardRepository->find($session->get('currentWineCard', 0));

        /** @var Product $product */
        $product = $this->em->getRepository(Product::class)->find($p_id);
        /** @var WineCard $wineCard */
        $wineCard = $this->em->getRepository(WineCard::class)->find($wc_id);

        if (false === $product OR false === $wineCard) {
            return new JsonResponse('Wrong ID', 400);
        }

        if ($checked) {
            $product->addWineCard($wineCard);
            $status = 'added';
        } else {
            $product->removeWineCard($wineCard);
            $status = 'removed';
        }

        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse([
            'status' => $status,
            'wineCardsCount' => $product->countWinecards()
        ], 200);
    }

    /**
     * @Route("cabinet/product/link/winecard", name="ajax_product_link_winecard", methods={"GET"})
     */
    public function ajaxProductLinkToWinecard(Request $request,
                                              SessionInterface $session,
                                              ProductRepository $productRepository,
                                              WineCardRepository $wineCardRepository)
    {
        /** @var Product $product */
        $product = $productRepository->find($request->query->get('product_id', false));
        /** @var WineCard $wineCard */
        $currentWineCard = $wineCardRepository->find($session->get('currentWineCard', 0));

        if (null === $product OR null === $currentWineCard) {
            return new JsonResponse('Wrong ID', 400);
        }

        if ($currentWineCard->getProducts()->contains($product)) {
            $currentWineCard->removeProduct($product);
            $result = 'removed';
        } else {
            $currentWineCard->addProduct($product);
            $product->addWineCard($currentWineCard);
            $result = 'added';
        }

        $this->em->persist($currentWineCard);
        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse([
            'result' => $result,
        ], 200);
    }

}
