<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\EventPic;
use App\Entity\EventProduct;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\WineColor;
use App\Entity\WineColorAlias;
use App\Entity\WineSugar;
use App\Entity\WineSugarAlias;
use App\Filter\ProductFilter;
use App\Filter\ShortProductFilter;
use App\Form\ProductType;
use App\Repository\AliasRepository;
use App\Repository\EventRepository;
use App\Repository\OfferRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use App\Repository\VendorRepository;
use App\Repository\WineColorAliasRepository;
use App\Repository\WineColorRepository;
use App\Repository\WineSugarAliasRepository;
use App\Repository\WineSugarRepository;
use App\Service\WineColorService;
use App\Service\WineSugarService;
use App\Utils\Slugger;
use Aws\S3\S3Client;
use Aws\S3\ObjectUploader;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\FileUploader;

class AdminProductController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'product';
    CONST ENTITY_NAME = 'Product';
    CONST NS_ENTITY_NAME = 'App:Product';

    /**
     * @Route("backend/test_s3", name="backend_test_s3", methods={"GET"})
     */
    public function testS3(Request $request)
    {
        $s3 = new S3Client([
            'version' 	=> 'latest',
            'region'  	=> 'default',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'	=> '6VHWXYOCRNJMZO6X116T',
                'secret' => 'l5dIzPCfXTDeCdlXFUlmTPBun18hnPsxuroGSYh6',
            ],
            'endpoint' => 'https://s3.regru.cloud/'
        ]);
        $listBuckets = $s3->listBuckets();
        echo '<pre>';
        var_export($listBuckets->toArray()['Buckets']);
        echo '</pre>';
        $source = fopen('/var/www/wine3/public/uploads/0ce3447c06f1566e3d8c4ef205f7ad1d.png', 'rb');

        $uploader = new ObjectUploader(
            $s3,
            'wine',
            'barbaresco-2020.png',
            $source
        );

        do {
            try {
                $result = $uploader->upload();
                if ($result["@metadata"]["statusCode"] == '200') {
                    print('<p>File successfully uploaded to ' . $result["ObjectURL"] . '.</p>');
                }
                print($result);
            } catch (MultipartUploadException $e) {
                rewind($source);
                $uploader = new MultipartUploader($s3, $source, [
                    'state' => $e->getState(),
                ]);
            }
        } while (!isset($result));

        fclose($source);

        echo '<pre>';
        var_export($result->toArray());
        echo '</pre>';
        exit();
    }

    /**
     * @Route("backend/product/toggle/field", name="ajax_product_toggle_field", methods={"GET"})
     */
    public function ajaxToggleFieldAction(Request $request)
    {
        $id = $request->query->get('id', false);
        $field = $request->query->get('field', false);

        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository(Product::class)->find($id);

        if (false === $product) {
            return new JsonResponse('Wrong ID', 400);
        }

        switch ($field) {
            case 'isActive':
                $status = !$product->getIsActive();
                $product->setIsActive($status);
                break;
            default:
                return new Response('<span class="badge badge-pill badge-secondary"><i class="fa fa-question"></i></span>', 200);
                break;
        }

        $em->persist($product);
        $em->flush();

        $response = sprintf('<span class="badge badge-pill badge-%s"><i class="fa fa-%s"></i></span>',
            ($status ? 'success': 'danger'),
            ($status ? 'check-square': 'window-close')
        );

        return new Response($response, 200);
    }

    /**
     * @Route("backend/event_product/{id}/delete", name="backend_event_product_delete", methods={"GET"})
     */
    public function ajaxDeleteEventProduct(EventProduct $eventProduct,
                                           Request $request,
                                           EventRepository $eventRepository)
    {
        // todo: check if ajax?
        $eventId = $request->query->get('event_id', null);
        $event = $eventRepository->find($eventId);

        if (null === $event) {
            throw new NotFoundHttpException();
        }

        $event->removeProduct($eventProduct);

        $this->em->persist($event);
        $this->em->remove($eventProduct);
        $this->em->flush();

        return new JsonResponse(['message' => 'success'], 200);
    }

    /**
     * @Route("backend/event_pic/{id}/delete", name="backend_event_pic_delete", methods={"GET"})
     */
    public function ajaxDeleteEventPic(EventPic $eventPic,
                                       Request $request,
                                       EventRepository $eventRepository)
    {
        // todo: check if ajax?
        $eventId = $request->query->get('event_id', null);
        $event = $eventRepository->find($eventId);

        if (null === $event) {
            throw new NotFoundHttpException();
        }

        $event->removeEventPic($eventPic);

        $this->em->persist($event);
        $this->em->remove($eventPic);
        $this->em->flush();

        return new JsonResponse(['message' => 'success'], 200);
    }

    /**
     * @Route("backend/ajax/product/search", name="backend_ajax_product_search", methods={"GET"})
     */
    public function ajaxSearchProduct(Request $request,
                                      ProductRepository $repository)
    {
        $searchString = $request->query->get('q', null);

        if (null === $searchString) {
            $elements = $repository->getTopTen();
        } else {
            $elements = $repository->ajaxSearch($searchString);
        }

        $res = [];

        /** @var Product $element */
        foreach ($elements as $element) {
            $res[] = [
                'id' => $element->getId(),
                'text' => $element->getName(),
                'extra' => [],
            ];
        }

        return $this->json(['items' => $res, 'hasMore' => false]);
    }

    /**
     * @Route("backend/product/ajax/price", name="ajax_product_get_price", methods={"GET"})
     */
    public function ajaxGetProductPriceAction(Request $request)
    {
        $id = $request->query->get('id', false);
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find($id);

        if (false === $product) {
            return new JsonResponse('Wrong ID', 400);
        }

        return new JsonResponse([
                'price' => $product->getPrice(),
                'priceIn' => $product->getPriceIn(),
            ], 200);
    }

    /**
     * @Route("backend/product/change/status", name="ajax_product_status", methods={"GET"})
     */
    public function ajaxStatusAction(Request $request)
    {
        $id = $request->query->get('id', false);

        $em = $this->getDoctrine()->getManager();

        $product = $em->getRepository(Product::class)->find($id);

        if (false === $product) {
            return new JsonResponse('Wrong ID', 400);
        }

        $status = $request->query->get('status');
        $product->setStatus($status);

        $em->persist($product);
        $em->flush($product);

        return new JsonResponse('ok', 200);
    }

    /**
     * Lists all product entities.
     *
     * @Route("backend/product/index", name="backend_product_index", methods={"GET"})
     */
    public function index(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, ProductFilter::class);

        return $this->render('admin/product/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'current_filters_string' => $this->current_filters_string,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Lists all product entities.
     *
     * @Route("backend/product/no_pic", name="backend_product_no_pic", methods={"GET"})
     */
    public function noPic(Request $request,
                          ProductRepository $productRepository,
                          SessionInterface $session)
    {
        $query = $productRepository->noPicProductQry();

        $pagination = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            self::ROWS_PER_PAGE  /*limit per page*/
        );

        $this->filter_form = $this->createForm(ShortProductFilter::class, null, array(
            'action' => $this->generateUrl('backend_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        return $this->render('admin/product/no_pic.html.twig', array(
            'pagination' => $pagination,
//            'current_filters' => $this->current_filters,
//            'current_filters_string' => $this->current_filters_string,
//            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    private function getPagination(Request $request,
                                   SessionInterface $session,
                                   string $filter_form_class,
                                   string $defaultField = 'id',
                                   string $defaultOrder = 'ASC')
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository(self::NS_ENTITY_NAME);

        $this->filter_form = $this->createForm($filter_form_class, null, array(
            'action' => $this->generateUrl('backend_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        /** @var Query $query */
        $query = $this->buildQuery($repository, $request, $session, $this->filter_form, self::MODEL, $defaultField, $defaultOrder);

        $pagination = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            self::ROWS_PER_PAGE  /*limit per page*/
        );

        return $pagination;
    }

    private function __buildQuery(EntityRepository $repository,
                                Request $request,
                                SessionInterface $session,
                                FormInterface $filter_form,
                                string $model,
                                string $defaultField = 'id',
                                string $defaultOrder = 'ASC')
    {
        $sort_by = $request->query->get('sort_by', $defaultField);
        $order = $request->query->get('order', $defaultOrder);
        $session_filters = $session->get('admin-filters', false);

        $query = $repository->createQueryBuilder($model);

        if (false !== $session_filters && count($session_filters) && isset($session_filters[$model])) {
            $this->current_filters = $session_filters[$model];
            $filter_form->submit($this->current_filters);

            $filterBuilder = $repository->createQueryBuilder($model);

            foreach ($session_filters[$model] as $filter => $value) {
                switch ($filter) {
                    case 'isEmptyPic':
//                        die($value);
                        $query->andWhere($model.'.announcePic IS NULL');
                        break;
                    case 'name':
//                        die($value);
                        $query->where($model.'.name LIKE :val')
                            ->setParameter('val', "%$value%")
                        ;
                        break;
                    default:
                        $this->current_filters_string[$filter] = $value;
                }
            }

            $this->query
//                ->addFilterConditions($filter_form, $filterBuilder)
                ->orderBy($model.'.'.$sort_by, $order)
            ;

            $query = $filterBuilder->getQuery();

            // vendor.id -> vendor.name
            foreach ($session_filters[$model] as $filter => $value) {
                switch ($filter) {
                    case 'isEmptyPic':
                        $country = $this->countryRepository->find($value);
                        $this->current_filters_string[$filter] = $country ?? $value;
                        break;
                    case 'country':
                        $country = $this->countryRepository->find($value);
                        $this->current_filters_string[$filter] = $country ?? $value;
                        break;
                    case 'vendor':
                        $vendor = $this->vendorRepository->find($value);
                        $this->current_filters_string[$filter] = $vendor ?? $value;
                        break;
                    default:
                        $this->current_filters_string[$filter] = $value;
                }
            }
        } else {
            $this->current_filters = null;
        }

        return $query
            ->orderBy($model.'.'.$sort_by, $order)
            ->getQuery();
    }

    /**
     * Creates a new product entity.
     *
     * @Route("backend/product/new", name="backend_product_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request,
                              ProductRepository $productRepository,
                              FileUploader $fileUploader,
                              SessionInterface $session,
                              OfferRepository $offerRepository)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            if (null !== $file = $product->getAnnouncePicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'announce');
                $product->setAnnouncePic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            if (null !== $file = $product->getContentPicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'content');
                $product->setContentPic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            if (null !== $file = $product->getExtraPicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'extra');
                $product->setExtraPic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            $product->setSlug($this->makeSlug($product, $productRepository));

            $offer = $offerRepository->find($session->get('offer_id', null));
            if ($offer) {
                $offer->setProduct($product);
                $this->em->persist($offer);
            }

            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_product_edit', array('id' => $product->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/product/new.html.twig', array(
            'row' => $product,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Product $product, ProductRepository $productRepository)
    {
        $slug = Slugger::urlSlug($product->getName(), array('transliterate' => true));

        while($productRepository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }


    /**
     * Displays a form to edit an existing product entity.
     *
     * @Route("backend/products/update", name="backend_products_update", methods={"POST"})
     */
    public function massUpdate(Request $request)
    {
        $items = $request->request->get('product');
        $em = $this->getDoctrine()->getManager();

        foreach ($items as $id => $item) {
            /** @var Product $product */
            $product = $em->getRepository(Product::class)->find($id);
            if (false === $product) {
                continue;
            }
            $product->setPrice($item['price']);

            $em->persist($product);
            $em->flush();
        }

        $this->addFlash('success', 'Your changes were saved!!!');

        // redirect to referer
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Всем товарам сгенерировать slug
     *
     * @Route("backend/products/slugify", name="backend_products_slugify", methods={"GET"})
     */
    public function slugifyAction(Request $request, ProductRepository $productRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $products = $productRepository->getWithVendor();

        foreach ($products as $product) {
            /** @var Product $product */
            if (!empty($product->getSlug())) {
                continue;
            }

            $product->setSlug($this->makeSlug($product, $productRepository));

            $em->persist($product);
        }

        $em->flush();
        die();

        $this->addFlash('success', 'Your changes were saved!');

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("backend/product/{id}/load_pic/{offer_id}", name="backend_product_pic_from_offer", methods={"GET"})
     */
    public function makeProductAction(Product $product, string $offer_id,
                                      Request $request,
                                      FileUploader $fileUploader,
                                      OfferRepository $offerRepository
    )
    {
        $offer = $offerRepository->find($offer_id);
        if (!$offer) {
            $this->addFlash('warning', 'Wrong OfferId');
            return $this->redirectToRoute('backend_product_edit', ['id' => $product->getId()]);
        }

        if (empty($offer->getPicUrl())) {
            $this->addFlash('warning', 'Empty Offer Pic URL');
            return $this->redirectToRoute('backend_product_edit', ['id' => $product->getId()]);
        }

        $picPathRelative = $fileUploader->makePng(
            $offer->getPicUrl(),
            $offer->getYmlId(),
            $offer->getImportYml() ? $offer->getImportYml()->getRotatePicAngle() : 0
        );
        if ($picPathRelative) {
            $product
                ->setContentPic($picPathRelative)
                ->setAnnouncePic($picPathRelative)
            ;
            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', 'Pic refreshed: ' . $picPathRelative);
        }

        return $this->redirectToRoute('backend_product_edit', ['id' => $product->getId()]);
    }
        /**
     * @Route("backend/product/{id}/edit", name="backend_product_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request,
                               Product $product,
                               ProductRepository $productRepository,
                               FileUploader $fileUploader
    )
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        // todo: remove it?
        $imageSubDir = floor($product->getId() / 200);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            if (null !== $file = $product->getAnnouncePicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'announce');
                $product->setAnnouncePic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            if (null !== $file = $product->getContentPicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'content');
                $product->setContentPic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            if (null !== $file = $product->getExtraPicFile()) {
                $fileName = $fileUploader->uploadProductPic($file, $product, 'extra');
                $product->setExtraPic($fileName);
                $this->cacheManager->remove('uploads/' . $fileName, 'thumb_square_50');
            }

            if (empty($product->getSlug())) {
                $product->setSlug($this->makeSlug($product, $productRepository));
            }

            $grapeSorts = $request->request->get('grapeSort', []);
            $res = [];
            foreach ($grapeSorts as $grapeSort) {
                $res[$grapeSort['name']] = $grapeSort['value'];
            }
            $product->setGrapeSort(json_encode($res));

            $ratings = $request->request->get('rating', []);
            $res = [];
            foreach ($ratings as $rating) {
                $res[$rating['name']] = $rating['value'];
            }
            $product->setRatings(json_encode($res));

            $user = $this->getUser();
            $product->setEditor($user);
            $product->setIsEdited(true);

            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_product_edit', array('id' => $product->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        // todo: create form again?
        $form = $this->createForm(ProductType::class, $product);


        $deleteForm = $this->createDeleteForm($product);

        return $this->render('admin/product/edit.html.twig', array(
            'row' => $product,
            'ratings' => json_decode($product->getRatings(), true),
            'grapeSorts' => json_decode($product->getGrapeSort(), true),
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * @Route("backend/product/no_supplier", name="backend_product_no_supplier", methods={"GET"})
     */
    public function findNoSupplier(ProductRepository $productRepository, SupplierRepository $supplierRepository)
    {
        $supplier = $supplierRepository->getFirstSupplier();

        if (null === $supplier) {
            throw new \Exception('No Supplier found');
        }

        $rows = $productRepository->findBy(['supplier' => null]);

        /** @var Product $product */
        foreach ($rows as $product) {
            $product->setSupplier($supplier);
            $this->em->persist($product);
        }

        $this->em->flush();

        return new Response(count($rows));
    }

    /**
     * @Route("backend/product/fix_sugars", name="backend_product_fix_sugars", methods={"GET"})
     */
    public function fixSugars(WineSugarService $wineSugarService,
                              ProductRepository $productRepository,
                              WineSugarAliasRepository $wineSugarAliasRepository)
    {
        $wineSugars = $wineSugarService->getWineSugars();
        $products = $productRepository->findAll();

        /** @var WineSugar $wineSugar */
        foreach ($products as $product) {
            // Alert! Wine sugar saved in Type
            $productSugar = mb_strtolower($product->getType());

            if (! isset($wineSugars[$productSugar])) {
                if (null === $wineSugarAlias = $wineSugarAliasRepository->findOneByName($productSugar)) {
                    $wineSugarAlias = (new WineSugarAlias())->setName($productSugar);
                    $this->em->persist($wineSugarAlias);
                    echo "$productSugar - new alias <br />";
                }
            } else {
                $product->setWineSugar($wineSugars[$productSugar]);
                $this->em->persist($product);
                echo "{$product->getId()}: {$product->getName()} sugar updated to {$wineSugars[$productSugar]->getName()} <br />";
            }
        }

        $this->em->flush();

        return new Response("done <br />");
    }

    /**
     * @Route("backend/product/fix_colors", name="backend_product_fix_colors", methods={"GET"})
     */
    public function fixColors(WineColorService $wineColorService,
                              ProductRepository $productRepository,
                              WineColorAliasRepository $wineColorAliasRepository)
    {
        $wineColors = $wineColorService->getWineColors();
        $products = $productRepository->findAll();

        /** @var WineColor $wineColor */
        foreach ($products as $product) {
            $productColor = mb_strtolower($product->getColor());

            if (! isset($wineColors[$productColor])) {
                if (null === $wineColorAlias = $wineColorAliasRepository->findOneByName($productColor)) {
                    $wineColorAlias = (new WineColorAlias())->setName($productColor);
                    $this->em->persist($wineColorAlias);
                    echo "$productColor - new color <br />";
                }
            } else {
                $product->setWineColor($wineColors[$productColor]);
                $this->em->persist($product);
                echo "{$product->getId()}: {$product->getName()} color updated to {$wineColors[$productColor]->getName()} <br />";
            }
        }

        $this->em->flush();

        return new Response("done <br />");
    }

    /**
     * @Route("backend/product/fix_ratings", name="backend_product_fix_ratings", methods={"GET"})
     */
    public function fixRatings(ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();
        foreach ($products as $product) {
            $ratings = $product->getRatings();
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
                $product->setRatings(json_encode($result));
            }

            $this->em->persist($product);
        }
        $this->em->flush();
        
        return new Response('ok');
    }

    /**
     * @Route("backend/product/fix_grape", name="backend_product_fix_grape", methods={"GET"})
     */
    public function fixGrape(ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();
        foreach ($products as $product) {
            $grapeSort = $product->getGrapeSort();
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
                    $product->setGrapeSort(json_encode($result));
                    $this->em->persist($product);
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
                $product->setGrapeSort(json_encode($result));
                $this->em->persist($product);
            }

        }
        $this->em->flush();
        
        return new Response('ok');
    }

    /**
     * @Route("backend/product/no_offer", name="backend_product_no_offer", methods={"GET"})
     */
    public function findNoOffer(ProductRepository $productRepository,
                                SupplierRepository $supplierRepository,
                                OfferRepository $offerRepository)
    {
        $supplier = $supplierRepository->getFirstSupplier();

        if (null === $supplier) {
            throw new \Exception('No Supplier found');
        }

        $rows = $productRepository->getJoinedOffers();

        $offersCreated = 0;
        /** @var Product $product */
        foreach ($rows as $product) {
            if (0 === count($product->getOffers())) {
                $offer = (new Offer())
                    ->setProduct($product)
                    ->setVendor($product->getVendor())
                    ->setSupplier($product->getSupplier())
                    ->setCategory($product->getCategory())
                    ->setRegion($product->getRegion())
                    ->setName($product->getName())
                    ->setPrice($product->getPrice())
                    ->setProductCode($product->getProductCode())
                    ->setSlug($product->getSlug())
                    ->setColor($product->getColor())
                    ->setType($product->getType())
                    ->setAlcohol($product->getAlcohol())
                    ->setGrapeSort($product->getGrapeSort())
                    ->setYear($product->getYear())
                    ->setRatings($product->getRatings())
                    ->setVolume($product->getVolume())
                    ->setServeTemperature($product->getServeTemperature())
                    ->setDecantation($product->getDecantation())
                    ->setPacking($product->getPacking())
                    ->setFermentation($product->getFermentation())
                    ->setAging($product->getAging())
                    ->setAgingType($product->getAgingType())
//                    ->set($product->get())
                ;
                $this->em->persist($offer);
                $offersCreated++;
            }
        }

        $this->em->flush();

        return new Response(sprintf('total products: %s, new offers: %s',
            count($rows), $offersCreated
        ));
    }

    /**
     * @Route("backend/product/fix_wine_colors", name="backend_product_fix_wine_colors", methods={"GET"})
     */
    public function fixWineColors(Request $request,
                                  ProductRepository $productRepository,
                                  WineColorRepository $wineColorRepository)
    {
        $wineColorsMapping = [];
        /** @var WineColor $wineColor */
        foreach ($wineColorRepository->getAllJoined() as $wineColor) {
            $color = mb_strtolower($wineColor->getName());
            $wineColorsMapping[mb_strtolower($wineColor->getName())] = $wineColor;
            foreach ($wineColor->getAliases() as $alias) {
                $color = mb_strtolower($alias->getName());
                if (! isset($wineColorsMapping[$color])) {

                }

            }
        }

        $products = $productRepository->findAll();

        foreach ($products as $product) {
            $productColor = mb_strtolower($product->getColor());
            if (isset($wineColorsMapping[$productColor])) {
                $product->setWineColor($wineColorsMapping[$productColor]);
                $this->em->persist($product);
            } else {
                $alias = (new WineColorAlias())
                    ->setName($productColor);
                $this->em->persist($alias);
                $this->em->flush();
            }
        }

//        $this->em->flush();

        return new Response(print_r($products, true));
    }

    /**
     * @Route("backend/product/wine_colors", name="backend_product_wine_colors", methods={"GET"})
     */
    public function wineColors(Request $request, ProductRepository $productRepository)
    {
        $rows = $productRepository->getAllWineColors();

        return new Response(print_r($rows, true));
    }



    /**
     * @Route("backend/product/{id}", name="backend_product_show", methods={"GET"})
     */
    public function showAction(Request $request, Product $product)
    {
        $isAjax = $request->isXmlHttpRequest();
        $template = $isAjax ? 'admin/product/show_ajax.html.twig' : 'admin/product/show.html.twig';

        return $this->render($template, array(
            'row' => $product,
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * @Route("backend/product/mass/turn_off", name="backend_product_mass_turn_off", methods={"GET"})
     */
    public function massTurnOff(ProductRepository $productRepository)
    {
        $productRepository->turnOffAll();
        $this->addFlash('success', 'All turned off');

        return $this->redirectToRoute('backend_product_index');
    }

    /**
     * @Route("backend/product/mass_delete", name="backend_product_mass_delete", methods={"POST"})
     */
    public function massDelete(Request $request,
                               FileUploader $fileUploader,
                               ProductRepository $productRepository,
                               OfferRepository $offerRepository)
    {
        $ids = $request->get('deleteId', []);

        if (0 === count($ids)) {
            $this->addFlash('danger', 'Please check at least one element.');
            return $this->redirectToRoute('backend_product_index');
        }

        foreach ($ids as $id) {
            $product = $productRepository->find($id);
            $fileUploader->removeProductPics($product);
            /** @var Offer $offer */
            foreach ($product->getOffers() as $offer) {
                $product->removeOffer($offer);
                $this->em->remove($offer);
            }
            $offerRepository->removeByProduct($product);
            $this->em->remove($product);
        }

        $this->em->flush();

        $this->addFlash('success', 'Rows deleted successfully.');

        return $this->redirectToRoute('backend_product_index');
    }

    /**
     * Deletes a product entity.
     *
     * @Route("backend/product/{id}/delete", name="backend_product_delete_now", methods={"DELETE"})
     */
    public function delete(Request $request, Product $product)
    {

    }

    /**
     * @Route("backend/products/delete_all", name="backend_products_delete_all", methods={"GET"})
     */
    public function deleteAll(Request $request,
                              FileUploader $fileUploader,
                              ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();
        $limit = 500;
        /** @var Product $product */
        foreach ($products as $i => $product) {
            $fileUploader->removeProductPics($product);
//            exit();
            $product->getOffers()->clear();
            $product->getFoods()->clear();

            $this->em->remove($product);
            if ($i === $limit) {
                break;
            }
        }

        $this->em->flush();

        $this->addFlash('success', 'Products deleted');
        return $this->redirectToRoute('backend_product_index');
        // flush
        // redirect
    }

    /**
     * Deletes a product entity.
     *
     * @Route("backend/product/{id}", name="backend_product_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Product $product)
    {
        $filter_form = $this->createDeleteForm($product);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush($product);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_product_index');
    }

    /**
     * Creates a form to delete a product entity.
     *
     * @param Product $product The product entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Product $product)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_product_delete', array('id' => $product->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

//    /**
//     * @Route("backend/product/fix_colors", name="backend_product_fix_colors", methods={"GET"})
//     */
//    public function fixColors(ProductRepository $productRepository,
//                              WineColorRepository $wineColorRepository,
//                              AliasRepository $aliasRepository)
//    {
//        $wineColorsDb = $wineColorRepository->findAll();
//        $wineColors = [];
//
//        foreach ($wineColorsDb as $wineColor) {
//            $aliases = $aliasRepository->getWineColorAliases($wineColor->getName());
//
//            $wineColors[$wineColor->getId()] = [
//                'name' => $wineColor->getName(),
//                'aliases' => array_values($aliases),
//            ];
//        }
//
//        return new Response(print_r($wineColors, true));
//    }

}
