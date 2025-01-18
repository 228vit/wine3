<?php

namespace App\Controller\Admin;

use App\DTO\OfferDTO;
use App\DTO\ProductDTO;
use App\Entity\Admin;
use App\Entity\Appellation;
use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Entity\ImportYml;
use App\Filter\ImportCsvFilter;
use App\Form\ImportYmlStep1Type;
use App\Repository\AppellationRepository;
use App\Repository\CategoryRepository;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\VendorRepository;
use App\Service\FileUploader;
use App\Service\OfferFactory;
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
    public function step1(ImportYml $import, Request $request, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ImportYmlStep1Type::class, $import);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $import->setStage(1);

            $this->em->persist($import);
            $this->em->flush();

            $this->addFlash('success', 'Успешно обновлено');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        if ($request->request->has('next')) {
            return $this->redirectToRoute('backend_import_yml_step2', ['id' => $import->getId()]);
        }

        return $this->render('admin/import_yml/step1.html.twig', [
            'row' => $import,
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
        $vendors = [];
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

        return $this->render('admin/import_yml/step3.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'inDbRegions' => $inDbRegions,
            'ymlRegions' => $regions,
        ]);
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
                                    CountryRepository $countryRepository,
                                    CountryRegionRepository $regionRepository): Response
    {
        dd($_REQUEST);
    }

    /**
     * @Route("/backend/import_yml/{id}/new_appellations", name="backend_import_yml_new_appellations", methods={"POST"})
     */
    public function newAppellations(ImportYml $importYml,
                                    Request $request,
                                    CountryRepository $countryRepository,
                                    CountryRegionRepository $regionRepository): Response
    {
//        dd($_REQUEST);
//        $countryMapping = json_decode($importYml->getCountriesMapping(), true);

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

        foreach ($request->request->get('newAppellation', []) as $regionId => $newApps) {
            foreach ($newApps as $ymlAppellationId => $newAppellationName) {
                if (!isset($inDbRegions[$regionId])) throw new \Exception('Wrong Region id:' . $regionId);

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
     * @Route("/backend/import_yml/{id}/step4", name="backend_import_yml_step4", methods={"GET", "POST"})
     */
    public function step4Apellations(ImportYml $importYml,
                          CountryRepository $countryRepository,
                          AppellationRepository $appellationRepository,
                          CountryRegionRepository $categoryRegionRepository): Response
    {
        $this->setStage($importYml, 4);
        $regionMapping = json_decode($importYml->getRegionsMapping(), true);

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

        $inDbAppellations = [];
        $allApps = $appellationRepository->allAsArray();
        foreach ($allApps as $row) {
//            $inDbAppellations[$row['id']] =  "({$row['c_name']} - {$row['r_name']}) {$row['name']}";
            $inDbAppellations[$row['id']] = $row['name'];
        }

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
        $data = simplexml_load_file($importYml->getUrl());
        $this->setStage($importYml, 5);

        $vendors = [];
        foreach ($data->shop->offers->offer as $row) {
            $avail = strval($row->attributes()->available);
            $avail = $avail === 'true' ? true : false;
            if (!$avail) continue;

            $vendors[] = trim(strval($row->vendor));
        }

        $inDbVendors = [];
        foreach ($vendorRepository->allAsArray() as $row) {
            $inDbVendors[$row['id']] = $row['name'];
        }

//        dd($inDbVendors);
//        return new Response('qqq');
        return $this->render('admin/import_yml/step5.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
            'inDbVendors' => $inDbVendors,
            'ymlVendors' => $vendors,
        ]);
    }

    /**
     * @Route("/backend/import_yml/{id}/step6", name="backend_import_yml_step6", methods={"POST"})
     */
    public function step6offers(ImportYml $importYml, VendorRepository $vendorRepository): Response
    {
        return $this->render('admin/import_yml/step6.html.twig', [
            'row' => $importYml,
            'importYml' => $importYml,
//            'inDbVendors' => $inDbVendors,
//            'ymlVendors' => $vendors,
        ]);
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
