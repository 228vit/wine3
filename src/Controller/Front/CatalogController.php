<?php
namespace App\Controller\Front;

use App\Entity\Country;
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
    public function indexAction(Request $request,
                                SessionInterface $session,
                                WineCardRepository $wineCardRepository,
                                ProductDataService $productDataService)
    {
        return $this->render('front/catalog/index.html.twig', array(
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

        $sessionFilters = $session->get('filters', []);
        $sessionFilters['product'] = $sessionFilters['product'] ?? [];
        $sessionFilters['product']['wineColor'] = $sessionFilters['product']['wineColor'] ?? [];
        $sessionFilters['product']['wineSugar'] = $sessionFilters['product']['wineSugar'] ?? [];
        $sessionFilters['product']['supplier'] = $sessionFilters['product']['supplier'] ?? [];
        $sessionFilters['product']['country'] = $sessionFilters['product']['country'] ?? [];
        $sessionFilters['product']['vendor'] = $sessionFilters['product']['vendor'] ?? [];
        $sessionFilters['product']['volume'] = $sessionFilters['product']['volume'] ?? [];
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

        return $this->render('front/catalog/filters.html.twig', array(
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
//            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }
}