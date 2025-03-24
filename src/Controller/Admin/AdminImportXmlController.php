<?php

namespace App\Controller\Admin;

use App\DTO\OfferDTO;
use App\DTO\ProductDTO;
use App\Entity\Admin;
use App\Entity\Appellation;
use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Entity\Food;
use App\Entity\GrapeSort;
use App\Entity\GrapeSortAlias;
use App\Entity\ImportYml;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\ProductGrapeSort;
use App\Entity\ProductRating;
use App\Entity\Rating;
use App\Entity\Vendor;
use App\Filter\ImportCsvFilter;
use App\Form\ImportYmlStep1Type;
use App\Repository\AppellationRepository;
use App\Repository\CategoryRepository;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\GrapeSortAliasRepository;
use App\Repository\GrapeSortRepository;
use App\Repository\OfferRepository;
use App\Repository\ProductGrapeSortRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\RatingRepository;
use App\Repository\VendorRepository;
use App\Service\FileUploader;
use App\Service\OfferFactory;
use App\Service\WineColorService;
use App\Service\WineSugarService;
use App\Utils\Slugger;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class AdminImportXmlController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'import_yml';
    CONST ENTITY_NAME = 'ImportYml';
    CONST NS_ENTITY_NAME = 'App:ImportYml';

    /**
     * @Route("/backend/import_yml/index", name="backend_import_yml_index", methods={"GET", "POST"})
     */
    public function index(Request $request, SessionInterface $session, FilterBuilderUpdaterInterface $query_builder_updater)
    {
        $pagination = $this->getPagination($request, $session, ImportCsvFilter::class, 'id', 'DESC');

        return $this->render('admin/import_yml/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'import.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'id',
                    'sortable' => true,
                ],
                'import.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'name',
                    'sortable' => true,
                ],
                'import.createdAt' => [
                    'title' => 'Created',
                    'row_field' => 'createdAt',
                    'sorting_field' => 'createdAt',
                    'sortable' => true,
                ],
            ]
        ));
    }

    /**
     * @Route("/backend/import_yml/new", name="backend_import_yml_new", methods={"GET", "POST"})
     */
    public function new(Request $request, FileUploader $fileUploader): Response
    {
        $import = new ImportYml();
//        $import->setAdmin($user);

        $form = $this->createForm(ImportYmlStep1Type::class, $import);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $import->setStage(1);
            $this->em->persist($import);
            $this->em->flush();

            return $this->redirectToRoute('backend_import_yml_step1', [
                'id' => $import->getId(),
            ]);

        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/import_yml/step1.html.twig', [
            'form' => $form->createView(),
            'model' => 'import',
            'mode' => 'create',
        ]);
    }

    /**
     * @Route("/backend/import_yml/{id}/step1", name="backend_import_yml_step1", methods={"GET", "POST"})
     */
    public function step1(ImportYml $importYml, Request $request, FileUploader $fileUploader): Response
    {
        $this->setStage($importYml, 1);
        $form = $this->createForm(ImportYmlStep1Type::class, $importYml);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($importYml);
            $this->em->flush();

            $this->addFlash('success', 'Успешно обновлено');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        if ($request->request->has('next')) {
            return $this->redirectToRoute('backend_import_yml_step2', ['id' => $importYml->getId()]);
        }

        return $this->render('admin/import_yml/step1.html.twig', [
            'row' => $importYml,
            'form' => $form->createView(),
            'model' => 'import_yml',
            'mode' => 'edit',
        ]);
    }

    /**
     * @Route("/backend/import_yml/{id}/step2", name="backend_import_yml_step2")
     */
    public function step2(ImportYml $importYml,
                          CountryRepository $countryRepository,
                          CountryRegionRepository $countryRegionRepository,
                          VendorRepository $vendorRepository,
                          Request $request): Response
    {
        $this->setStage($importYml, 2);
        $data = simplexml_load_file($importYml->getUrl());

        $productCategories = [];

        foreach ($data->shop->offers->offer as $row) {
            $avail = strval($row->attributes()->available);
            $avail = $avail === 'true' ? true : false;
            if (!$avail) continue;

            $categoryId = strval($row->categoryId);
            $productCategories[$categoryId] = $categoryId;
        }

        $inDbCountries = [];
        $allC = $countryRepository->allAsArray();
        foreach ($allC as $country) {
            $inDbCountries[$country['id']] = $country['name'];
        }

        $inDbRegions = [];
        $allR = $countryRegionRepository->allAsArray();
        /** @var CountryRegion $region */
        foreach ($allR as $region) {
            $inDbRegions[$region['id']] =  "({$region['c_name']}) {$region['name']}";
        }

        $inDbVendors = [];
        $allV = $vendorRepository->allAsArray();
        foreach ($allV as $vendor) {
            $inDbVendors[$vendor['id']] = ucfirst(trim($vendor['name']));
        }

        $this->setStage($importYml, 2);
        
        if (null === $importYml->getUrl()) {
            $this->addFlash('danger', 'URL не определён!');
            return $this->redirectToRoute('backend_import_yml_step1', ['id' => $importYml->getId()]);
        }

        if (false === $data) {
            $this->addFlash('danger', 'URL не читается!');
            return $this->redirectToRoute('backend_import_yml_step1', ['id' => $importYml->getId()]);
        }

        $categories = [];
        $countries = [];
        $regions = [];
        $vendors = [];
        $root = null;

        /*
         * <category id="00070566">Вино</category>
            <category id="00071753" parentId="00070566">ARGENTINA</category>
            <category id="00071754" parentId="00071753">Catena Zapata</category>
            <category id="00071760" parentId="00070566">AUSTRALIA</category>
            <category id="00071762" parentId="00071760">Henschke</category>
         */

        // get Countries / Regions / Appellations
        foreach ($data->shop->categories->category as $row) {
            $id = strval($row['id']);
            $name = ucwords(strtolower(strval($row)));

            if (!isset($row['parentId'])) {
                $categories[$id] = [
                    'id' => $id,
                    'name' => $name,
                ];
                continue;
            }

            $parentId = strval($row['parentId']);

            // регион или аппеласьон - если ему принадлежат товары
            if (array_key_exists($id, $productCategories)) {
                $vendors[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'country_name' => isset($countries[$parentId]) ? ucfirst($countries[$parentId]['name']) : null,
                    'region_name' => isset($regions[$parentId]) ? ucfirst($regions[$parentId]['name']) : null,
                ];
            }

            // страна внутри вина
            if (array_key_exists($parentId, $categories)) {
                $countries[$id] = [
                    'id' => $id,
                    'name'=> $name
                ];
            }

            // регион внутри страны
            if (array_key_exists($parentId, $countries)) {
                $regions[$id] = [
                    'id' => $id,
                    'country_id' => $parentId,
                    'country_name' => (isset($countries[$parentId]) ? $countries[$parentId]['name'] : 'not set'),
                    'name'=> $name
                ];
            }
        } // foreach ($data->shop->categories->category as $row)

        $countriesMapping = $importYml->getCountriesMapping();

        if (!empty($countriesMapping)) {
            $countriesMapping = json_decode($countriesMapping, true); // 'ymlCountryId': dbCountryId
        }

        return $this->render('admin/import_yml/step2.html.twig', [
            'row' => $importYml,
            'inDbCountries' => $inDbCountries,
            'inDbRegions' => $inDbRegions,
            'inDbVendors' => $inDbVendors,
            'ymlCountries' => $countries,
            'ymlRegions' => $regions,
            'ymlVendors' => $vendors,
            'countriesMapping' => $countriesMapping,
        ]);
    }


    /**
     * @Route("/backend/import_yml/{id}/step3", name="backend_import_yml_step3")
     */
    public function step3(ImportYml $importYml,
                          CountryRepository $countryRepository,
                          CountryRegionRepository $countryRegionRepository,
                          VendorRepository $vendorRepository,
                          Request $request): Response
    {
        $this->setStage($importYml, 3);
        $data = simplexml_load_file($importYml->getUrl());

        $inDbRegions = [];
        $allR = $countryRegionRepository->allAsArray();
        /** @var CountryRegion $region */
        foreach ($allR as $region) {
            $inDbRegions[$region['id']] =  "({$region['c_name']}) {$region['name']}";
        }

        $categories = [];
        $countries = [];
        $regions = [];
        $root = null;

        foreach ($data->shop->categories->category as $row) {
            $id = strval($row['id']);
            $name = ucwords(strtolower(strval($row)));

            if (!isset($row['parentId'])) {
                $categories[$id] = [
                    'id' => $id,
                    'name' => $name,
                ];
                continue;
            }

            $parentId = strval($row['parentId']);

            // страна внутри вина
            if (array_key_exists($parentId, $categories)) {
                $countries[$id] = [
                    'id' => $id,
                    'name'=> $name
                ];
            }

            // регион внутри страны
            if (array_key_exists($parentId, $countries)) {
                $regions[$id] = [
                    'id' => $id,
                    'country_id' => $parentId,
                    'country_name' => (isset($countries[$parentId]) ? $countries[$parentId]['name'] : 'not set'),
                    'name'=> $name
                ];
            }
        } // foreach ($data->shop->categories->category as $row)

        $regionsMapping = $importYml->getRegionsMapping();

        if (!empty($regionsMapping)) {
            $regionsMapping = json_decode($regionsMapping, true); // 'ymlId': dbId
        }

        return $this->render('admin/import_yml/step3.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'inDbRegions' => $inDbRegions,
            'ymlRegions' => $regions,
            'regionsMapping' => $regionsMapping,
        ]);
    }


    /**
     * @Route("/backend/import_yml/{id}/step4", name="backend_import_yml_step4", methods={"GET", "POST"})
     */
    public function step4Apellations(ImportYml $importYml,
                                     CountryRepository $countryRepository,
                                     AppellationRepository $appellationRepository,
                                     CountryRegionRepository $categoryRegionRepository): Response
    {
        $this->setStage($importYml, 4);
        $regionMapping = json_decode($importYml->getRegionsMapping(), true);
//        dd($regionMapping);
//        "00071754" => "394"
//        "00071762" => "395"
//        "00073058" => "396"

        $data = simplexml_load_file($importYml->getUrl());

        $appellations = [];
        // Appellation - если он принадлежит региону
        foreach ($data->shop->categories->category as $row) {
            $id = strval($row['id']);
            $name = ucwords(strtolower(strval($row)));

            if (!isset($row['parentId'])) continue; // roots

            $parentId = strval($row['parentId']);
            if (isset($regionMapping[$parentId])) {
                $regionId = intval($regionMapping[$parentId]);
                $region = $categoryRegionRepository->find($regionId);
                if (!$region) continue;

                $appellations[] = [
                    'ymlId' => $id,
                    'name' => $name,
                    'region' => [
                        'id' => $region->getId(),
                        'name' => $region->getName()
                    ],
                    'country' => [
                        'id' => $region->getCountry()->getId(),
                        'name' => $region->getCountry()->getName(),
                    ]
                ];
            }
        }

//        dd($appellations);

        $inDbAppellations = [];
//        $allApps
        $inDbAppellations = $appellationRepository->allAsArray();
//        foreach ($allApps as $row) {
//            $inDbAppellations[$row['id']] =  "({$row['c_name']} - {$row['r_name']}) {$row['name']}";
//            $inDbAppellations[$row['id']] = $row['name'];
//        }

        return $this->render('admin/import_yml/step4.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'inDbAppellations' => $inDbAppellations,
            'ymlAppellations' => $appellations,
        ]);
//        dd($appellations);
    }

    /**
     * @Route("/backend/import_yml/{id}/step5", name="backend_import_yml_step5", methods={"GET"})
     */
    public function step5Vendors(ImportYml $importYml, VendorRepository $vendorRepository): Response
    {
        $this->setStage($importYml, 5);
        $data = simplexml_load_file($importYml->getUrl());

        $onlyVendors = [];
        foreach ($data->shop->offers->offer as $row) {
            $avail = strval($row->attributes()->available);
            $avail = $avail === 'true' ? true : false;
            if (!$avail) continue;

//            $vendorName = strval($row->vendor);
            $vendorName = $this->getYmlParam($row,'tovmarka');
            if (!empty($vendorName)) {
                $onlyVendors[$vendorName] = $vendorName;
            }

//            foreach ($row->param as $param) {
//                if ($param->attributes()->name == 'tovmarka') {
//                    $vendorName = trim(strval($param));
//                    if (empty($vendorName)) continue;
//                }
//                if ($param->attributes()->name == 'strana') {
//                    $countryName = trim(strval($param));
//                }
//            }
//            if (!empty($vendorName)) {
//                $onlyVendors[$vendorName] = $vendorName;
//            }
//
//            if (!isset($vendorName[$countryName][$vendorName])) {
//                $vendors[$countryName][$vendorName] = $vendorName;
//            }
        }

//        sort($onlyVendors);
//        dd($onlyVendors);

        $inDbVendors = [];
        foreach ($vendorRepository->allAsArray() as $row) {
            $inDbVendors[$row['id']] = $row['name'];
        }

        return $this->render('admin/import_yml/step5.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'inDbVendors' => $inDbVendors,
            'ymlVendors' => $onlyVendors,
        ]);
    }

    /**
     * @Route("/backend/pixel/transp", name="backend_pixel_transp", methods={"GET"})
     */
    public function pixelTransparent()
    {
        $rotationAngle = 270;
        $url = 'https://wine-dp-trade.ru/756483/wine/00074911_1.png';
//        $url = 'http://wine3.local/00075210_1.png';
//        $url = 'http://wine3.local/00075210_1.jpg';
        // jpg white 255, 255, 255, alpha 0
        // png white 255, 255, 255, alpha 0
        // png transp 255, 255, 255, alpha 0
        try {
            $info = pathinfo($url);
            $extension = strtolower($info['extension']);
            $isPng = false;

            switch ($extension) {
                case "png":
                    $image = imagecreatefrompng($url);
                    $isPng = true;
                    break;
                case "gif":
                    $image = imagecreatefromgif($url);
                    break;
                default:
                    $image = imagecreatefromjpeg($url);
            }

            if (!$image) {
                die('Failed to load image');
            }
            imagealphablending($image, true);
            imagesavealpha($image, true);

            if ($rotationAngle !== 0) {
                $image = imagerotate($image, $rotationAngle, 0);
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $newImage = imagecreatetruecolor($width, $height);
            imagesavealpha($newImage, true);

            // заливаем всё прозрачкой
            $transparency = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparency);

            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $colors = imagecolorsforindex($image, imagecolorat($image, $x, $y));
//                    /*
//                     * red => 98, green => 98, blue => 98
//                     */
//                    // если цвет околого белого - не копируем, его заменит прозрачный фон
                    // !$isPng AND
                    if ($colors['red'] <= 235 AND $colors['green'] <= 235 AND $colors['blue'] <= 235) {
                        $pixel = imagecolorat($image, $x, $y);
                        imagesetpixel($newImage, $x, $y, $pixel);
                    }

//                        $pixel = imagecolorallocatealpha(
//                            $newImage, // ?
//                            0, 0, 0, 127);
//                        imagesetpixel($newImage, $x, $y, $pixel);
//                    } else {
                }
            }

            $this->uploadsDirectory = $this->getParameter('uploads_directory');
            $this->productPicsSubDirectory = $this->getParameter('product_pics_subdirectory');

            $fileName = 'offer_' . rand(100000, 999999) . '.png';
            $path = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $this->productPicsSubDirectory
                . DIRECTORY_SEPARATOR . $fileName;

            if (!file_exists($path)) {
                $f = fopen($path, 'w');
                fclose($f);
            }

            imagepng($newImage, $path);
            imagedestroy($image);
            imagedestroy($newImage);


            dd($this->productPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName);
        } catch (\Exception $e) {
            return new Response($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return new Response('done');
    }

//    private function isTransparent(array($arr))
//    {
//
//    }

    /**
     * @Route("/backend/import_yml/{id}/make_offers", name="backend_import_yml_make_offers", methods={"GET"})
     */
    public function step7makeOffers(ImportYml $importYml,
                                    CountryRepository $countryRepository,
                                    CountryRegionRepository $regionRepository,
                                    AppellationRepository $appellationRepository,
                                    OfferRepository $offerRepository,
                                    VendorRepository $vendorRepository,
                                    WineColorService $wineColorService,
                                    WineSugarService $wineSugarService,
                                    FileUploader $fileUploader): Response
    {
        $data = simplexml_load_file($importYml->getUrl());
        $limit = 50;
        $currentRow = 0;
        $countries = json_decode($importYml->getCountriesMapping(), true);
        $regions = json_decode($importYml->getRegionsMapping(), true);
        $appellations = json_decode($importYml->getAppellationsMapping(), true);
        $vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"

        foreach ($data->shop->offers->offer as $row) {
            $offerId = strval($row->attributes()->id);
            $isActive = boolval($row->attributes()->available);
            $price = floatval($row->price);
            $name = strval($row->name);
            $barcode = isset($row->barcode) ? strval($row->barcode) : null;
            $vendorName = $this->getYmlParam($row, 'tovmarka');
            $picUrl = strval($row->picture);

            $vendor = null;
            if (isset($vendors[$vendorName])) {
                $vendorId = $vendors[$vendorName];
                $vendor = $vendorRepository->find($vendorId);
            } //? $vendors[$vendorName] : null;

            /** @var Offer $offer */
            $offer = $offerRepository->findOneBy([
                'ymlId' => $offerId,
            ]);

            if ($offer) {
                $offer->setPrice($price)
                    ->setPicUrl($picUrl)
                    ->setIsActive($isActive)
                    ->setVendor($vendor)
                ;
                $this->em->persist($offer);
                $this->em->flush();
//
                $this->makeProduct($offer, $wineColorService, $wineSugarService, $fileUploader);
                echo "update offer: {$offer->getName()} <br>";
                ++$currentRow;

                if ($currentRow >= $limit) break;

                continue;
            }

            $description = strval($row->description);
            $categoryId = strval($row->categoryId); // country - region - appel-tion
            $appellation = null;
            $region = null;
            $country = null;
            $wineColor = $this->getYmlParam($row, 'typenom');
            $wineSugar = $this->getYmlParam($row, 'vidvina');
            $year = intval($this->getYmlParam($row, 'year'));
            $volume = floatval($this->getYmlParam($row, 'vol')); // 0.75l
            $alcohol = floatval($this->getYmlParam($row, 'degree')); // 0.75l

            if (isset($appellations[$categoryId])) {
                $appellationInDb = $appellationRepository->find($appellations[$categoryId]);
                if ($appellationInDb) {
                    $appellation = $appellationInDb;
                    $region = $appellationInDb->getCountryRegion();
                    $country = $appellationInDb->getCountry();
                }
            }

            if (isset($regions[$categoryId])) {
                $regionInDb = $regionRepository->find($regions[$categoryId]);
                if ($regionInDb) {
                    $region = $regionInDb;
                    $country = $regionInDb->getCountry();
                }
            }

            if (isset($countries[$categoryId])) {
                $countryInDb = $countryRepository->find($countries[$categoryId]);
                if ($countryInDb) {
                    $country = $countryInDb;
                }
            }

            $grapeSort[1] = $this->getYmlParam($row, 'sortvin1');
            $valueGrapeSort[1] = $this->getYmlParam($row, 'dolyasort1');
            $grapeSort[2] = $this->getYmlParam($row, 'sortvin2');
            $valueGrapeSort[2] = $this->getYmlParam($row, 'dolyasort2');
            $grapeSort[3] = $this->getYmlParam($row, 'sortvin3');
            $valueGrapeSort[3] = $this->getYmlParam($row, 'dolyasort3');
            $grapeSort[4] = $this->getYmlParam($row, 'sortvin4');
            $valueGrapeSort[4] = $this->getYmlParam($row, 'dolyasort4');
            $grapeSort[5] = $this->getYmlParam($row, 'sortvin5');
            $valueGrapeSort[5] = $this->getYmlParam($row, 'dolyasort5');

            $grapeSort = array_filter($grapeSort);
            $valueGrapeSort = array_filter($valueGrapeSort);

            $grapeSorts = [];
            if (count($grapeSort) == count($valueGrapeSort)) {
                $grapeSorts = array_combine($grapeSort, $valueGrapeSort);
            }

            $offer = (new Offer())
                ->setImportYml($importYml)
                ->setYmlId($offerId)
                ->setIsActive($isActive)
                ->setName($name)
                ->setBarcode($barcode)
                ->setDescription($description)
                ->setSlug(Slugger::urlSlug($name))
                ->setPrice($price)
                ->setCountry($country)
                ->setRegion($region)
                ->setAppellation($appellation)
                ->setVendor($vendor)
                ->setSupplier($importYml->getSupplier())
                ->setYear($year)
                ->setVolume($volume)
                ->setAlcohol($alcohol)
                ->setType($wineSugar)
                ->setColor($wineColor)
                ->setGrapeSort(json_encode($grapeSorts))
                ->setPicUrl($picUrl)
            ;
            echo "create offer: {$offer->getName()} <br>";

            $this->em->persist($offer);
            $this->em->flush();

            $this->makeProduct($offer, $wineColorService, $wineSugarService, $fileUploader);

            ++$currentRow;

            if ($currentRow >= $limit) break;
        }

        die();

        return $this->redirectToRoute('backend_import_yml_step6', [
            'id' => $importYml->getId()
        ]);
    }

    private function makeProduct(Offer $offer,
                                 WineColorService $wineColorService,
                                 WineSugarService $wineSugarService,
                                 FileUploader $fileUploader)
    {
        $grapeSortRepository = $this->em->getRepository(GrapeSort::class);
        $grapeSortAliasRepository = $this->em->getRepository(GrapeSortAlias::class);
        $productGrapeSortRepository = $this->em->getRepository(ProductGrapeSort::class);
        $ratingRepository = $this->em->getRepository(Rating::class);
        $productRepository = $this->em->getRepository(Product::class);
        $productRatingRepository = $this->em->getRepository(ProductRating::class);
        $productRatingRepository = $this->em->getRepository(ProductRating::class);
        /** @var Product $product */
        $product = $productRepository->findOneBy([
            'barcode' => $offer->getBarcode(),
        ]);

        if (null === $product) {
            $product = $productRepository->findOneBy([
                'name' => $offer->getName(),
            ]);
        }

        if ($product) {
            $product->addOffer($offer)
                ->setIsActive($offer->getIsActive())
                ->setPrice($offer->getPrice())
                ->setVendor($offer->getVendor())
            ;
            $this->em->persist($product);
            $this->em->flush();
            return true;
        }

        $product = (new Product())
            ->setIsActive($offer->getIsActive())
            ->setName($offer->getName())
            ->setContent($offer->getDescription())
            ->setVendor($offer->getVendor())
            ->setCategory($offer->getCategory())
            ->setCountry($offer->getCountry())
            ->setRegion($offer->getRegion())
            ->setName($offer->getName())
            ->setSlug($offer->getSlug())
            ->setBarcode($offer->getBarcode())
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
                $offer->getYmlId(),
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
//        foreach ($offer->getFoods() as $food) {
//            $product->addFood($food);
//        }

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

        $product->addOffer($offer);
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * @Route("/backend/import_yml/{id}/step6", name="backend_import_yml_step6", methods={"GET"})
     */
    public function step6ViewOffers(ImportYml $importYml,
                                CountryRepository $countryRepository,
                                CountryRegionRepository $regionRepository,
                                AppellationRepository $appellationRepository,
                                OfferRepository $offerRepository,
                                VendorRepository $vendorRepository): Response
    {
        $this->setStage($importYml, 6);
        $data = simplexml_load_file($importYml->getUrl());

        $countries = json_decode($importYml->getCountriesMapping(), true);
        $regions = json_decode($importYml->getRegionsMapping(), true);
        $appellations = json_decode($importYml->getAppellationsMapping(), true);
        $vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"
        $supplierOffers = $offerRepository->getSupplierOffers($importYml->getSupplier());

        $offers = [];
        foreach ($data->shop->offers->offer as $row) {
            $offerId = strval($row->attributes()->id);
            $avail = strval($row->attributes()->available);
            $avail = $avail === 'true' ? true : false;
            if (!$avail) continue;

            $name = strval($row->name);
            $description = strval($row->description);
            $categoryId = strval($row->categoryId);
            $price = strval($row->price);
            $pic = strval($row->picture);
            $appellation = null;
            $region = null;
            $country = null;

            if (isset($appellations[$categoryId])) {
                $appellationInDb = $appellationRepository->find($appellations[$categoryId]);
                if ($appellationInDb) {
                    $appellation = $appellationInDb->getName();
                    $region = $appellationInDb->getCountryRegion()->getName();
                    $country = $appellationInDb->getCountry()->getName();
                }
            }

            if (isset($regions[$categoryId])) {
                $regionInDb = $regionRepository->find($regions[$categoryId]);
                if ($regionInDb) {
                    $region = $regionInDb->getName();
                    $country = $regionInDb->getCountry()->getName();
                }
            }
            if (isset($countries[$categoryId])) {
               $countryInDb = $countryRepository->find($countries[$categoryId]);
               if ($countryInDb) {
                   $country = $countryInDb->getName();
               }
            }

            $vendorName = $this->getYmlParam($row, 'tovmarka');
//            $vendorName = strval($row->vendor);;
            $vendor = null;
            if (isset($vendors[$vendorName])) {
                $vendor = $vendorRepository->find($vendors[$vendorName]);
                $vendor = $vendor ? $vendor->getName() : null;
            } //? $vendors[$vendorName] : null;

            $grapeSort1 = $this->getYmlParam($row, 'sortvin1');
            $valueGrapeSort1 = $this->getYmlParam($row, 'dolyasort1');
            $grapeSort2 = $this->getYmlParam($row, 'sortvin2');
            $valueGrapeSort2 = $this->getYmlParam($row, 'dolyasort2');
            $grapeSort3 = $this->getYmlParam($row, 'sortvin3');
            $valueGrapeSort3 = $this->getYmlParam($row, 'dolyasort3');
            $grapeSort4 = $this->getYmlParam($row, 'sortvin4');
            $valueGrapeSort4 = $this->getYmlParam($row, 'dolyasort4');

            $wineColor = $this->getYmlParam($row, 'typenom');
            $sugar = $this->getYmlParam($row, 'vidvina');
            $alcohol = floatval($this->getYmlParam($row, 'degree'));
            $volume = floatval($this->getYmlParam($row, 'vol'));

            $offers[$offerId] = [
                'name' => $name,
                'description' => $description,
                'price' => floatval($price),
                'pic' => $pic,
                'country' => $country,
                'region' => $region,
                'appellation' => $appellation,
                'vendor' => $vendor,
                'volume' => $volume,
                'alcohol' => $alcohol,
                'grapeSorts' => implode ("\n", [
                   "{$grapeSort1}: {$valueGrapeSort1}",
                   "{$grapeSort2}: {$valueGrapeSort2}",
                   "{$grapeSort3}: {$valueGrapeSort3}",
                   "{$grapeSort4}: {$valueGrapeSort4}",
                ]),
                'wineColor' => $wineColor,
                'sugar' => $sugar,
                'productOfferId' => isset($supplierOffers[$offerId]) ? $supplierOffers[$offerId] : null,
            ];
        }

        return $this->render('admin/import_yml/step6.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'offers' => $offers,
        ]);
    }

    private function getYmlParam($row, $name = 'name')
    {
        foreach ($row->param as $param) {
            if ($param->attributes()->name == $name) {
                return trim(strval($param));
            }
        }

        return null;
    }

    /**
     * @Route("/backend/import_yml/{id}/new_countries", name="backend_import_yml_new_countries", methods={"POST"})
     */
    public function newCountries(ImportYml $importYml,
                                 Request $request,
                                 CountryRepository $countryRepository): Response
    {
        $countries = [];
        foreach ($request->request->get('country', []) as $ymlCountryId => $countryId) {
            if (empty($countryId)) continue;
            $countries[$ymlCountryId] = $countryId;
        }

        foreach ($request->request->get('newCountry', []) as $ymlCountryId => $countryName) {
            $countryName = ucwords(strtolower($countryName));
            $country = $countryRepository->findOneBy([
                'name' => $countryName,
            ]);

            if ($country) {
                $countries[$ymlCountryId] = $country->getId();
                continue;
            }

            $country = (new Country())
                ->setName($countryName);
            $this->em->persist($country);
            $this->em->flush();

            $countries[$ymlCountryId] = $country->getId();
        }

        $importYml->setCountriesMapping(json_encode($countries));

        $this->addFlash('success', 'Country Mapping saved');

        $this->em->persist($importYml);
        $this->em->flush();

        return $this->redirectToRoute('backend_import_yml_step2', [
            'id' => $importYml->getId(),
        ]);
    }

    /**
     * @Route("/backend/import_yml/{id}/new_offer/{yml_id}", name="backend_import_yml_new_offer", methods={"GET"})
     */
    public function newOffer(ImportYml $importYml,
                             string $yml_id,
                             CountryRepository $countryRepository,
                             VendorRepository $vendorRepository,
                             AppellationRepository $appellationRepository,
                             CountryRegionRepository $regionRepository): Response
    {
        $data = simplexml_load_file($importYml->getUrl());

        $countries = json_decode($importYml->getCountriesMapping(), true);
        $regions = json_decode($importYml->getRegionsMapping(), true);
        $appellations = json_decode($importYml->getAppellationsMapping(), true);
        $vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"

        foreach ($data->shop->offers->offer as $row) {
            $offerId = strval($row->attributes()->id);
            if ($yml_id != $offerId) continue;

            $name = strval($row->name);
            $description = strval($row->description);
            $categoryId = strval($row->categoryId); // country - region - appel-tion
            $price = strval($row->price);
            $picUrl = strval($row->picture);
            $appellation = null;
            $region = null;
            $country = null;
            $wineColor = $this->getYmlParam($row, 'typenom');
            $wineSugar = $this->getYmlParam($row, 'vidvina');
            $year = intval($this->getYmlParam($row, 'year'));
            $volume = floatval($this->getYmlParam($row, 'vol')); // 0.75l
            $alcohol = floatval($this->getYmlParam($row, 'degree')); // 0.75l

            if (isset($appellations[$categoryId])) {
                $appellationInDb = $appellationRepository->find($appellations[$categoryId]);
                if ($appellationInDb) {
                    $appellation = $appellationInDb;
                    $region = $appellationInDb->getCountryRegion();
                    $country = $appellationInDb->getCountry();
                }
            }

            if (isset($regions[$categoryId])) {
                $regionInDb = $regionRepository->find($regions[$categoryId]);
                if ($regionInDb) {
                    $region = $regionInDb;
                    $country = $regionInDb->getCountry();
                }
            }

            if (isset($countries[$categoryId])) {
                $countryInDb = $countryRepository->find($countries[$categoryId]);
                if ($countryInDb) {
                    $country = $countryInDb;
                }
            }

//            $vendorName = $this->getYmlParam($row, 'tovmarka');
            $vendorName = strval($row->vendor);

            $vendor = null;
            if (isset($vendors[$vendorName])) {
                $vendorId = $vendors[$vendorName];
                $vendor = $vendorRepository->find($vendorId);
            } //? $vendors[$vendorName] : null;

            $grapeSort[1] = $this->getYmlParam($row, 'sortvin1');
            $valueGrapeSort[1] = $this->getYmlParam($row, 'dolyasort1');
            $grapeSort[2] = $this->getYmlParam($row, 'sortvin2');
            $valueGrapeSort[2] = $this->getYmlParam($row, 'dolyasort2');
            $grapeSort[3] = $this->getYmlParam($row, 'sortvin3');
            $valueGrapeSort[3] = $this->getYmlParam($row, 'dolyasort3');
            $grapeSort[4] = $this->getYmlParam($row, 'sortvin4');
            $valueGrapeSort[4] = $this->getYmlParam($row, 'dolyasort4');
            $grapeSort[5] = $this->getYmlParam($row, 'sortvin5');
            $valueGrapeSort[5] = $this->getYmlParam($row, 'dolyasort5');

            $grapeSort = array_filter($grapeSort);
            $valueGrapeSort = array_filter($valueGrapeSort);

            $grapeSorts = array_combine($grapeSort, $valueGrapeSort);

            $offer = (new Offer())
                ->setImportYml($importYml)
                ->setYmlId($offerId)
                ->setName($name)
                ->setDescription($description)
                ->setSlug(Slugger::urlSlug($name))
                ->setPrice(floatval($price))
                ->setCountry($country)
                ->setRegion($region)
                ->setAppellation($appellation)
                ->setVendor($vendor)
                ->setSupplier($importYml->getSupplier())
                ->setYear($year)
                ->setVolume($volume)
                ->setAlcohol($alcohol)
                ->setType($wineSugar)
                ->setColor($wineColor)
                ->setGrapeSort(json_encode($grapeSorts))
                ->setPicUrl($picUrl)
            ;

            $this->em->persist($offer);
            $this->em->flush();

            return $this->redirectToRoute('backend_offer_link', [
                'id' => $offer->getId()
            ]);
        }

        return new Response('Wrong YML id');
    }

    /**
     * @Route("/backend/import_yml/{id}/new_regions", name="backend_import_yml_new_regions", methods={"POST"})
     */
    public function newRegions(ImportYml $importYml,
                               Request $request,
                               CountryRepository $countryRepository,
                               CountryRegionRepository $regionRepository): Response
    {
        $countryMapping = json_decode($importYml->getCountriesMapping(), true);
        $inDbCountries = [];
        $allC = $countryRepository->allAsArray();
        foreach ($allC as $country) {
            $inDbCountries[$country['id']] = $country['name'];
        }

        $regions = [];

        foreach ($request->request->get('newRegion', []) as $ymlCountryId => $ymlRegions) {
            if (isset($countryMapping[$ymlCountryId])) {
                $countryId = $countryMapping[$ymlCountryId];
                $country = $countryRepository->find($countryId);
                if (!$country) throw new \Exception('Country id not found: ' . $countryId);

                foreach ($ymlRegions as $ymlRegionId => $ymlRegionName) {
                    $region = (new CountryRegion())
                        ->setName($ymlRegionName)
                        ->setCountry($country);
                    $this->em->persist($region);

                    $regions[$ymlRegionId] = $countryId;
                }
                $this->em->flush();
            }
        }

        $regions = [];
        // filter possible empty values
        foreach ($request->request->get('mapCountryRegion', []) as $ymlRegionId => $countryRegionId) {
            if (empty($countryRegionId)) continue;
            $regions[$ymlRegionId] = $countryRegionId;
        }

        $importYml->setRegionsMapping(json_encode($regions));

        $this->em->persist($importYml);
        $this->em->flush();

        $this->addFlash('success', 'Changes saved!');

        return $this->redirectToRoute('backend_import_yml_step3', [
            'id' => $importYml->getId(),
        ]);
    }

    /**
     * @Route("/backend/import_yml/{id}/new_vendors", name="backend_import_yml_new_vendors", methods={"POST"})
     */
    public function newVendors(ImportYml $importYml,
                                    Request $request,
                                    VendorRepository $vendorRepository,
                                    CountryRegionRepository $regionRepository): Response
    {
        /* "Gaja" => "140"
         * "Terras Gauda" => "141" */
        $vendorMapping = array_filter($request->request->get('mapVendor', []));
        $newVendors = array_filter($request->request->get('newVendor', []));

//        dd($vendorMapping);

        foreach ($newVendors as $vendorName) {
            $vendorExist = $vendorRepository->findOneBy([
                'name' => $vendorName,
            ]);

            if ($vendorExist) continue;

            $vendor = (new Vendor())
                ->setName($vendorName)
                ->setSlug(Slugger::urlSlug($vendorName))
                ->setCountry(null)
            ;

            $this->em->persist($vendor);
            $this->em->flush();
            $vendorMapping[$vendor->getName()] = $vendor->getId();
        }

        $importYml->setVendorsMapping(json_encode($vendorMapping));

        $this->em->persist($importYml);
        $this->em->flush();

        $this->addFlash('success', "Vendors are mapped!");
//        dd($vendorMapping);

        return $this->redirectToRoute('backend_import_yml_step5', ['id' => $importYml->getId()]);
    }

    /**
     * @Route("/backend/import_yml/{id}/new_appellations", name="backend_import_yml_new_appellations", methods={"POST"})
     */
    public function newAppellations(ImportYml $importYml,
                                    Request $request,
                                    CountryRepository $countryRepository,
                                    CountryRegionRepository $regionRepository): Response
    {
        // 1. map existing
        /** @var array $appellationMapping */
        $appellationMapping = $request->request->get('mapAppellation', []);
        $appellationMapping = array_filter($appellationMapping);

//        dd($appellationMapping);

        // 2. create and map new
        $inDbCountries = [];
        $inDbRegions = [];
        $regions = $regionRepository->findAll();
        foreach ($regions as $region) {
            $inDbRegions[$region->getId()] = $region;
            $inDbCountries[$region->getCountry()->getId()] = $region->getCountry();
        }

        $appellationMapping = [];
        foreach ($request->request->get('mapAppellation', []) as $ymlAppellationId => $appellationId) {
            if (empty($appellationId)) continue;
            $appellationMapping[$ymlAppellationId] = $appellationId;
        }

        foreach ($request->request->get('newAppellation', []) as $regionId => $ymlNewAppellation) {
            foreach ($ymlNewAppellation as $ymlAppellationId => $newAppellationName) {

                /** @var CountryRegion $region */
                $region = $inDbRegions[$regionId];
                $country = $region->getCountry();
                $appellation = (new Appellation())
                    ->setName($newAppellationName)
                    ->setCountryRegion($region)
                    ->setCountry($country);
                $this->em->persist($appellation);
                $this->em->flush();;

                $appellationMapping[$ymlAppellationId] = $appellation->getId();
            }
        }

        $importYml->setAppellationsMapping(json_encode($appellationMapping));
        $this->em->persist($importYml);
        $this->em->flush();

        return $this->redirectToRoute('backend_import_yml_step4', ['id' => $importYml->getId()]);

    }


    /**
     * @Route("/backend/old_import_yml/step3/{id}", name="old_backend_import_yml_step3")
     */
    public function __importStep3(ImportYml $importYml,
                                FileUploader $fileUploader): Response
    {
        $this->setStage($importYml, 3);

        dd($_REQUEST['newCountry']);

        /*
         * тут массив в формате номер колонки => имя поля в БД
         */
        $csvColumnMapping = json_decode($importYml->getFieldsMapping(), true);
        $csvColumnMapping = null === $csvColumnMapping ? [] : array_filter($csvColumnMapping);

        if (0 === count($csvColumnMapping)) {
            $this->addFlash('danger', 'Не выбрано ни одного соответствия!');
            return $this->redirectToRoute('backend_import_yml_step2', ['id' => $importYml->getId()]);
        }

        $reverseArr = [];
        foreach ($csvColumnMapping as $colNum => $colName) {
            preg_match('/(\w+)\[(\w*)\]/i', $colName, $matches); // grapeSort[], ratings[WS]
            if ($matches) {
                if (empty($matches[2])) {
                    $reverseArr[$matches[1]][] = $colNum; // [grapeSort][] = 10
                } else {
                    $reverseArr[$matches[1]][$matches[2]] = $colNum; // [rating][WS] = 100
                }
            } else {
                $reverseArr[$colName] = $colNum;
            }
        }
        $handle = fopen($fileUploader->getUploadedCsvPath($importYml), 'r');

        $dataToReview = [];

        $i = 0;
        // надо подготовить данные для проверки перед отправкой в БД
        while (false !== $csvData = fgetcsv($handle, 4000, $importYml->getCsvDelimiter())) {
            $i++;
            if (1 === $i AND $importYml->isFileContainHeader()) {
                continue;
            }
            $newRow = [];
            // заберём из строки только нужные столбцы
            foreach ($reverseArr as $entityFieldName => $csvColNum) {
                // productCode || foods[]
                if (is_array($csvColNum)) {
                    $valueParts = [];
                    // foods[] => 15 || ratings[WS] => 20
                    foreach ($csvColNum as $index => $subCsvColNum) {
                        $subCsvColNum = (int)$subCsvColNum;
                        if (empty($csvData[$subCsvColNum])) {
                            continue;
                        }

                        $valueParts[] = (is_string($index) ? $index . '-' : '') . $csvData[$subCsvColNum];
                    }
                    $valueParts = array_filter($valueParts);

                    // todo: use ternary
                    if (0 !== count($valueParts)) {
                        $implodedValue = implode('<br />', $valueParts);
                        $newRow[$entityFieldName] = $implodedValue;
                    } else {
                        $newRow[$entityFieldName] = '';
                    }

                    continue;
                }

                $csvColNum = (int)$csvColNum;
                if (!is_int($csvColNum)) {
                    throw new InvalidArgumentException("Invalid mapping column: {$csvColNum} => {$entityFieldName} "
                        . print_r($csvData)
                    );
                }

                if (!isset($csvData[$csvColNum])) {
                    throw new InvalidArgumentException("Invalid mapping column: {$csvColNum} => {$entityFieldName} ");
                }

                $newRow[$entityFieldName] = $csvData[$csvColNum];

            }

            $dataToReview[] = new ProductDTO($newRow);

        } // while read file

        return $this->render('admin/import_yml/step3.html.twig', [
            'importLog' => $importYml,
            'dataToReview' => $dataToReview,
        ]);
    } // step 3

    private function parceGrapeSorts($grapeSorts): array
    {
        $result = [];
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
            } else if (2 == count($parts) AND is_int($parts[1])) {
                $result[trim($parts[0])] = 0;
            } else {
                $result[$sort] = 0;
            }
        }

        return $result;
    }


    /**
     * @Route("/backend/import_yml/copy/{id}", name="backend_import_yml_copy")
     */
    public function copy(ImportYml $importYml)
    {
        /** @var Admin $admin */
        $admin = $this->getUser();

        $clone = clone $importYml;
        $clone->setName('change me')
            ->setAdmin($admin)
            ->setStage(1)
            ->setIsComplete(false)
        ;

        $this->em->persist($clone);
        $this->em->flush();

        return $this->redirectToRoute('backend_import_yml_step1', ['id' => $clone->getId()]);
    }

    private function setStage(ImportYml $importYml, int $stage)
    {
        $importYml->setStage($stage);
        $this->em->persist($importYml);
        $this->em->flush();
    }

    /**
     * @Route("/backend/import_yml/{id}/delete", name="backend_import_yml_delete", methods={"GET", "POST"})
     */
    public function delete(ImportYml $importYml, Request $request)
    {
        $this->em->remove($importYml);
        $this->em->flush();

        return $this->redirectToRoute('backend_import_yml_index');
    }

    /**
     * @Route("/backend/png", name="backend_png", methods={"GET", "POST"})
     */
    public function png(FileUploader $fileUploader)
    {
        $url = 'https://wine-dp-trade.ru/756483/wine/00074546_1.png';

        $imgPath = $fileUploader->makePng($url, '756483', 270);

        dd($imgPath);
    }

    /**
     * @Route("/backend/import_yml/xml/dp-trade", name="backend_import_yml_yml-dp-traid", methods={"GET", "POST"})
     */
    public function importDpTrade(Request $request, SessionInterface $session, FilterBuilderUpdaterInterface $query_builder_updater)
    {
        $url = 'http://wine-dp-trade.ru/756483/ДП-ТРЕЙД_yml.xml';
        $data = simplexml_load_file($url);

        // todo: wine categories?
        $root = null;
        foreach ($data->shop->categories->category as $row) {
            $name = strval($row);
            $id = intval($row['id']);
            if ('Вино' === $name) {
                $root = $id; continue;
            }
        }

        if (null === $root) {
            die('No ROOT found');
        }

        $countries = [];
        foreach ($data->shop->categories->category as $row) {
            $id = intval($row['id']);
            $parentId = intval($row['parentId']);
            $name = strval($row);

            if ($parentId === $root) {
                $countries[$id] = $name;
            }
        }

        $regions = [];
        foreach ($data->shop->categories->category as $row) {
            $id = intval($row['id']);
            $parentId = intval($row['parentId']);
            $name = strval($row);

            if (in_array($parentId, $countries)) {
                $regions[$id] = [
                    'id' => $id,
                    'country_id' => $parentId,
                    'country_name' => (isset($countries[$parentId]) ? $countries[$parentId] : 'not set'),
                    'name'=> $name
                ];
            }
        }

        $vendors = [];
        foreach ($data->shop->categories->category as $row) {
            $id = intval($row['id']);
            $parentId = intval($row['parentId']);
            $name = strval($row);

            if (in_array($parentId, $regions)) {
                $region = $regions[$parentId];

                $vendors[] = [
                    'id' => $id,
                    'region_id' => $region['id'],
                    'region_name' => $region['name'],
                    'name'=> $name
                ];
            }
        }

        $products = [];
        foreach ($data->shop->offers->offer as $row) {
            $id = intval($row['id']);
            $vendorId = intval($row['id']);
            $available = ($row['available'] === 'true' ? true : false);
            $products[] = [
                'id' => $id,
                'available' => $available,
                'name' => $row->name,
                'description' => $row->description,
                'price' => $row->price,
                'currency' => $row->currencyId,
                'vendor' => (isset($vendors[$vendorId]) ? $vendors[$vendorId]['name'] : $vendorId),
                'country' => (isset($vendors[$vendorId]) ? $vendors[$vendorId]['country_name'] : null),
            ];
        }

        return $this->render('admin/import_yml/dp_trade.html.twig', array(
            'countries' => $countries,
            'products' => $products,
            'vendors' => $vendors,
        ));
    }

}
