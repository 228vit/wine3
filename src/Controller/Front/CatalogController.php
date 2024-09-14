<?php
namespace App\Controller\Front;

use App\Entity\Country;
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


class CatalogController extends AbstractController
{
    use FrontTraitController;

    CONST ROWS_PER_PAGE = 20;
    CONST MODEL = 'product';
    CONST ENTITY_NAME = 'Product';
    CONST NS_ENTITY_NAME = 'App:Product';
    private $currentFilters = [];


    /**
     * @Route("/catalog", name="front_catalog")
     */
    public function index(Request $request,
                                SessionInterface $session,
                                WineCardRepository $wineCardRepository,
                                ProductDataService $productDataService)
    {
        $session_order_field = $session->get('order_field', 'name');
        $orderMapping = [
            'name' => 'По названию',
            'price' => 'По цене',
            'country' => 'По стране',
        ];

        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);

        return $this->render('front/catalog/index.html.twig', array(
            'pagination' => $pagination,
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
     * @Route("/catalog/products", name="catalog_product_index")
     */
    public function filteredProducts(Request $request,
                                     SessionInterface $session,
                                     ProductDataService $productDataService)
    {
        $session_order_field = $session->get('order_field', 'name');
        $orderMapping = [
            'name' => 'По названию',
            'price' => 'По цене',
            'country' => 'По стране',
        ];

        $pagination = $this->getPagination($request, $session, $productDataService, FrontProductFilter::class);

        return $this->render('front/catalog/products.html.twig', array(
            'pagination' => $pagination,
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
     * @Route("/catalog/product/{id}/show", name="catalog_product_show")
     */
    public function show(Request $request, Product $product)
    {

        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'front/catalog/ajax_product.html.twig' : 'front/catalog/product.html.twig';

        return $this->render($template, array(
            'row' => $product,
        ));
    }

    /**
     * @Route("/catalog/filters", name="catalog_filters")
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
        $sessionFilters['product'] = $sessionFilters['product'] ?? [];
        $sessionFilters['product']['grapeSort'] = $sessionFilters['product']['grapeSort'] ?? [];
        $sessionFilters['product']['wineColor'] = $sessionFilters['product']['wineColor'] ?? [];
        $sessionFilters['product']['wineSugar'] = $sessionFilters['product']['wineSugar'] ?? [];
        $sessionFilters['product']['supplier'] = $sessionFilters['product']['supplier'] ?? [];
        $sessionFilters['product']['country'] = $sessionFilters['product']['country'] ?? [];
        $sessionFilters['product']['vendor'] = $sessionFilters['product']['vendor'] ?? [];
        $sessionFilters['product']['volume'] = $sessionFilters['product']['volume'] ?? [];
        $sessionFilters['product']['grapeSort'] = $sessionFilters['product']['grapeSort'] ?? [];
        $sessionFilters['product']['year'] = $sessionFilters['product']['year'] ?? '';
        $sessionFilters['product']['price_from'] = $sessionFilters['product']['price_from'] ?? '';
        $sessionFilters['product']['price_to'] = $sessionFilters['product']['price_to'] ?? '';
        $productFilters = null !== $sessionFilters AND isset($sessionFilters['product']) ?
            $sessionFilters['product'] : [];

        $suppliers = $supplierRepository->findBy([], ['name' => 'ASC']);
        $wineColors = $wineColorRepository->findAll();
        $wineSugars = $wineSugarRepository->findAll();
        $countries = $countryRepository->findWithWines();
        $grapeSorts = $grapeSortRepository->findBy([], ['name' => 'ASC']);
        $bottleVolumes = $productDataService->getBottleVolumes();

        return $this->render('front/catalog/filters.html.twig', array(
            'sessionFilters' => $sessionFilters,
            'productFilters' => $productFilters,
            'suppliers' => $suppliers,
            'wineColors' => $wineColors,
            'wineSugars' => $wineSugars,
            'countries' => $countries,
            'grapeSorts' => $grapeSorts,
            'bottleVolumes' => $bottleVolumes,
            'current_filters' => $this->current_filters,
            'currentFilters' => $this->currentFilters,
            'current_filters_string' => $this->current_filters_string,
            'filter_form' => $this->filter_form->createView(),
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
        $session_filters = $session->get('filters', false);
        $session_order_field = $session->get('order_field', 'name');
        $session_order_direction = $session->get('order_direction', 'asc');

//        dd($session_filters);
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
                    case 'grapeSort':
                        $grapeSorts = $this->grapeSortRepository->findAllByIds($value);
                        /** @var GrapeSort $grapeSort */
                        foreach ($grapeSorts as $grapeSort) {
                            $this->currentFilters[$filter][] = [
                                'name' => $grapeSort->getName(),
                                'value' => $grapeSort->getId(),
                            ];
                        }

                        $query->andWhere($model.'.grapeSort IN (:grapeSort)')->setParameter('grapeSort', $value);
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

        return $query;
    }

    /**
     * @Route("catalog/reset/product_filter", name="catalog_reset_product_filter")
     */
    public function resetProductFilter(SessionInterface $session)
    {
        $current_filters = $session->get('filters', false);
        if (isset($current_filters[self::MODEL])) {
            unset($current_filters[self::MODEL]);
        }

        $session->set('filters', $current_filters);

        return $this->redirectToRoute('front_catalog');
    }

}