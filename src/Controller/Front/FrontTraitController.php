<?php

namespace App\Controller\Front;

use App\Repository\CategoryRepository;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\GrapeSortRepository;
use App\Repository\ProductGrapeSortRepository;
use App\Repository\ProductRepository;
use App\Repository\VendorRepository;
use App\Repository\WineColorRepository;
use App\Repository\WineSugarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Knp\Component\Pager\PaginatorInterface;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

trait FrontTraitController
{

    private $current_filters = null;
    private $current_filters_string = null;
    /** @var FormInterface */
    private $filter_form;
    private $em;
    private $paginator;
    private $query_builder_updater;
    private $productRepository;
    private $countryRepository;
    private $vendorRepository;
    private $wineColorRepository;
    private $wineSugarRepository;
    private $grapeSortRepository;
    private $productGrapeSortRepository;
    private $cacheManager;

    public function __construct(EntityManagerInterface $em,
                                PaginatorInterface $paginator,
                                FilterBuilderUpdaterInterface $query_builder_updater,
                                ProductRepository $productRepository,
                                WineColorRepository $wineColorRepository,
                                WineSugarRepository $wineSugarRepository,
                                CountryRepository $countryRepository,
                                VendorRepository $vendorRepository,
                                GrapeSortRepository $grapeSortRepository,
                                ProductGrapeSortRepository $productGrapeSortRepository,
                                CacheManager $cacheManager)
    {
        $this->em = $em;
        $this->paginator = $paginator;
        $this->query_builder_updater = $query_builder_updater;
        $this->productRepository = $productRepository;
        $this->countryRepository = $countryRepository;
        $this->vendorRepository = $vendorRepository;
        $this->wineColorRepository = $wineColorRepository;
        $this->wineSugarRepository = $wineSugarRepository;
        $this->grapeSortRepository = $grapeSortRepository;
        $this->productGrapeSortRepository = $productGrapeSortRepository;
        $this->cacheManager = $cacheManager;
    }

    private function getPagination(Request $request,
                                   SessionInterface $session,
                                   string $filter_form_class)
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository(self::NS_ENTITY_NAME);

        $this->filter_form = $this->createForm($filter_form_class, null, array(
            'action' => $this->generateUrl('cabinet_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        /** @var Query $query */
        $query = $this->buildQuery($repository, $request, $session, $this->filter_form, self::MODEL);

        $pagination = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            self::ROWS_PER_PAGE  /*limit per page*/
        );

        return $pagination;
    }

    private function buildQuery(EntityRepository $repository,
                                Request $request,
                                SessionInterface $session,
                                FormInterface $filter_form,
                                string $model)
    {
        $sort_by = $request->query->get('sort_by', 'id');
        $order = $request->query->get('order', 'asc');
        $session_filters = $session->get('filters', false);

        if (false !== $session_filters && count($session_filters) && isset($session_filters[$model])) {
            $this->current_filters = $session_filters[$model];
            $filter_form->submit($this->current_filters);

            $filterBuilder = $repository->createQueryBuilder($model);
            
            $this->query_builder_updater
                ->addFilterConditions($filter_form, $filterBuilder)
                ->orderBy($model.'.'.$sort_by, $order)
            ;

            $query = $filterBuilder->getQuery();


            foreach ($session_filters[$model] as $filter => $value) {
                switch ($filter) {
                    case 'country':
                        $country = $this->countryRepository->find($value);
                        $this->current_filters_string[$filter] = $country ?? $value;
                        break;
                    case 'vendor':
                        $vendor = $this->vendorRepository->find($value);
                        $this->current_filters_string[$filter] = $vendor ?? $value;
                        break;
                    case 'year':
                        $this->current_filters_string['год'] = $value;
                        break;
                    default:
                        $this->current_filters_string[$filter] = $value;
                }
            }

        } else {
            $this->current_filters = null;
            // default query w/sorting
            $query = $repository->createQueryBuilder($model)
                ->orderBy($model.'.'.$sort_by, $order)
                ->getQuery();
        }

        return $query;
    }

    /**
     * Save filter values in session.
     *
     * @Route("/cabinet/apply/filter/{model}", name="cabinet_apply_filter", methods={"POST"})
     */
    public function saveModelFilter(Request $request, SessionInterface $session, $model)
    {
        $filters = $request->request->get('item_filter');
        // todo: validate?
        // unset empty values
        if (is_array($filters)) {
            $filters = array_filter($filters, function($value) { return $value !== ''; });
            // csrf???
            unset($filters['_token']);
        }
        // save filter to session
        $session->set('filters', array(
            $model => $filters,
        ));

        $referer = $request->headers->get('referer');
        $urlParts = parse_url($referer);
        unset($urlParts['query']);

        // todo: remove it?
        $url = $urlParts['scheme'].'//'.$urlParts['host'].$urlParts['path'];

        return $this->redirect($referer);
    }

    /**
     * @Route("/cabinet/reset/filter/{model}", name="cabinet_reset_filter")
     */
    public function resetModelFilter(Request $request, SessionInterface $session, $model)
    {
        $current_filters = $session->get('filters', false);
        if (isset($current_filters[$model])) {
            unset($current_filters[$model]);
        }

        $session->set('filters', $current_filters);
        $referer = $request->headers->get('referer');
        $urlParts = parse_url($referer);
        unset($urlParts['query']);

        $url = $urlParts['scheme'].'//'.$urlParts['host'].$urlParts['path'];
        // redirect to referer
//        return $this->redirect($request->headers->get('referer'));
        return $this->redirect($url);
    }


}