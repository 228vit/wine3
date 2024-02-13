<?php

namespace App\Controller\Front;

use App\Repository\CountryRepository;
use App\Repository\GrapeSortRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\WineColorRepository;
use App\Repository\WineSugarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterController extends AbstractController
{

    /**
     * @Route("/cabinet/filters", name="homepage_filters")
     */
    public function renderFilters(SessionInterface $session,
                            SupplierRepository $supplierRepository,
                            WineColorRepository $wineColorRepository,
                            WineSugarRepository $wineSugarRepository,
                            GrapeSortRepository $grapeSortRepository,
                            CountryRepository $countryRepository): Response
    {
        $sessionFilters = $session->get('filters', []);
        $productFilters = null !== $sessionFilters AND isset($sessionFilters['product']) ?
            $sessionFilters['product'] : [];

        $sessionFilters['product'] = $sessionFilters['product'] ?? [];
        $sessionFilters['product']['wineColor'] = $sessionFilters['product']['wineColor'] ?? [];

        $suppliers = $supplierRepository->findBy([], ['name' => 'ASC']);
        $wineColors = $wineColorRepository->findAll();
        $wineSugars = $wineSugarRepository->findAll();
        $countries = $countryRepository->findBy([], ['name' => 'ASC']);
        $grapeSorts = $grapeSortRepository->findBy([], ['name' => 'ASC']);

        return $this->render('front/filters/filters.html.twig', [
            'sessionFilters' => $sessionFilters,
            'productFilters' => $productFilters,
            'suppliers' => $suppliers,
            'wineColors' => $wineColors,
            'wineSugars' => $wineSugars,
            'countries' => $countries,
            'grapeSorts' => $grapeSorts,
        ]);
    }

    /**
     * @Route("/cabinet/filter/set", name="filter_set")
     */
    public function setFilter(Request $request, SessionInterface $session)
    {
        $session_filters = $session->get('filters', false);
        $name = $request->query->get('name', null);
        $value = $request->query->get('name', null);
        $checked = (bool)$request->query->get('name', null);

        if (null === $name OR null === $value) {
            return new Response(json_encode($_GET), 400);
        }

        $session_filters[$$name[$value]] = $checked;

        return new Response(json_encode($_GET));
    }

    /**
     * @Route("/cabinet/filter/remove", name="filter_remove")
     */
    public function removeFilter(Request $request, SessionInterface $session)
    {
        $session_filters = $session->get('filters', false);
        $name = $request->query->get('name', null);
        $value = $request->query->get('name', null);
        $checked = (bool)$request->query->get('name', null);

        if (null === $name OR null === $value) {
            return new Response(json_encode($_GET), 400);
        }

        $session_filters[$$name[$value]] = $checked;

        return new Response(json_encode($_GET));
    }


}