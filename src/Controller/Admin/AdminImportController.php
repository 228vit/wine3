<?php

namespace App\Controller\Admin;

use App\DTO\OfferDTO;
use App\DTO\ProductDTO;
use App\Entity\Admin;
use App\Entity\ImportLog;
use App\Filter\ImportCsvFilter;
use App\Form\ImportStep1Type;
use App\Service\FileUploader;
use App\Service\OfferFactory;
use App\Service\ProductFactory;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class AdminImportController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'import';
    CONST ENTITY_NAME = 'ImportLog';
    CONST NS_ENTITY_NAME = 'App:ImportLog';

    /**
     * @Route("/backend/import/yml/dp-trade", name="backend_import_yml-dp-traid", methods={"GET", "POST"})
     */
    public function ymlDpTraid(Request $request, SessionInterface $session, FilterBuilderUpdaterInterface $query_builder_updater)
    {
        $url = 'http://wine-dp-trade.ru/756483/ДП-ТРЕЙД_yml.xml';
        $data = simplexml_load_file($url);

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
//            var_dump($id, $parentId, $name);
        }

        $vendors = [];
        foreach ($data->shop->categories->category as $row) {
            $id = intval($row['id']);
            $parentId = intval($row['parentId']);
            $name = strval($row);

            if (in_array($parentId, $countries)) {
                $vendors[] = [
                    'id' => $id,
                    'country_id' => $parentId,
                    'country_name' => (isset($countries[$parentId]) ? $countries[$parentId] : 'not set'),
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

        return $this->render('admin/import/dp_trade.html.twig', array(
            'countries' => $countries,
            'products' => $products,
            'vendors' => $vendors,
        ));
    }

    /**
     * @Route("/backend/import/index", name="backend_import_index", methods={"GET", "POST"})
     */
    public function indexAction(Request $request, SessionInterface $session, FilterBuilderUpdaterInterface $query_builder_updater)
    {
        $pagination = $this->getPagination($request, $session, ImportCsvFilter::class, 'id', 'DESC');

        return $this->render('admin/import/index.html.twig', array(
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
     * @Route("/backend/import/new", name="backend_import_new", methods={"GET", "POST"})
     */
    public function new(Request $request, FileUploader $fileUploader): Response
    {
        /** @var Admin $user */
        $user = $this->getUser();

        $import = new ImportLog();
        $import->setAdmin($user);

        $form = $this->createForm(ImportStep1Type::class, $import);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $import->setStage(1);

            // todo: тут какая то фигня
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            if (null !== $file = $import->getCsvFile()) {
                $this->em->persist($import);
                $this->em->flush();
                $fileName = $fileUploader->uploadImportCsv($file, $import);
                $import->setCsv($fileName);

                $this->em->persist($import);
                $this->em->flush();
                return $this->redirectToRoute('backend_import_step1', ['id' => $import->getId()]);
            } else {

            }

        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/import/step1.html.twig', [
            'form' => $form->createView(),
            'model' => 'import',
            'mode' => 'create',
        ]);
    }

    /**
     * @Route("/backend/import/step1/{id}", name="backend_import_step1", methods={"GET", "POST"})
     */
    public function importStep1(ImportLog $import, Request $request, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ImportStep1Type::class, $import);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $import->setStage(1);

            // todo: тут какая то фигня
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            if (null !== $file = $import->getCsvFile()) {
                $this->em->persist($import);
                $this->em->flush();
                $fileName = $fileUploader->uploadImportCsv($file, $import);
                $import->setCsv($fileName);
            }

            $this->em->persist($import);
            $this->em->flush();

            $this->addFlash('success', 'Успешно обновлено');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        if ($request->request->has('next')) {
            return $this->redirectToRoute('backend_import_step2', ['id' => $import->getId()]);
        }

        return $this->render('admin/import/step1.html.twig', [
            'row' => $import,
            'csvPath' => $fileUploader->getUploadedCsvPath($import),
            'form' => $form->createView(),
            'model' => 'import',
            'mode' => 'edit',
        ]);
    }

    /**
     * @Route("/backend/import/step2/{id}", name="backend_import_step2")
     */
    public function importStep2(ImportLog $importLog,
                                Request $request,
                                FileUploader $fileUploader): Response
    {
        $this->setStage($importLog, 2);

        if (null === $importLog->getCsv()) {
            $this->addFlash('danger', 'CSV файл не загружен!');

            return $this->redirectToRoute('backend_import_step1', ['id' => $importLog->getId()]);
        }

        $handle = fopen($fileUploader->getUploadedCsvPath($importLog), 'r');

        // read header and example data row
        while (false !== $data = fgetcsv($handle, 4000, $importLog->getCsvDelimiter())) {
            $csvData[] = $data;
        }

        $headerArr = $importLog->isFileContainHeader() ? $csvData[0] : [];
        $exampleRow = $importLog->isFileContainHeader() ? $csvData[1] : $csvData[0] ;

        $importFields = [
            'Артикул' => 'productCode',
            'Название' => 'name',
            'Цвет' => 'color',
            'Сахар(сладкое/сухое)' => 'type',
            'Страна' => 'country',
            'Регион' => 'region',
            'Объём (л)' => 'volume',
            'Градус' => 'alcohol',
            'Год выпуска' => 'year',
            'Цена' => 'price',
            'Темп.подачи' => 'serveTemperature',
            'Производитель' => 'vendorName',
            'Сайт производителя' => 'vendorUrl',
            'Сорт винограда' => 'grapeSort[]',
            'Сочетается с продуктом' => 'foods[]',
            'Декантация' => 'decantation',
            'Рейтинг PR' => 'ratings[PR]',
            'Рейтинг WS' => 'ratings[WS]',
            'Рейтинг WE' => 'ratings[WE]',
            'Рейтинг ST' => 'ratings[ST]',
            'Рейтинг W&S' => 'ratings[W&S]',
            'Рейтинг JR' => 'ratings[JR]',
            'Рейтинг GP' => 'ratings[GP]',
            'Рейтинг FM' => 'ratings[FM]',
            'Рейтинг B' => 'ratings[B]',
            'Рейтинг Dec' => 'ratings[Dec]',
            'Рейтинг Due' => 'ratings[Due]',
            'Рейтинг GR' => 'ratings[GR]',
            'Рейтинг GH' => 'ratings[GH]',
            'Рейтинг IWR' => 'ratings[IWR]',
            'Рейтинг JH' => 'ratings[JH]',
            'Рейтинг JS' => 'ratings[JS]',
            'Рейтинг QUA-100' => 'ratings[QUA-100]',
            'Рейтинг QUA-20' => 'ratings[QUA-20]',
            'Рейтинг JO-100' => 'ratings[JO-100]',
            'Рейтинг JO-20' => 'ratings[JO-20]',
            'Рейтинг RFV' => 'ratings[RFV]',
            'Рейтинг LE-20' => 'ratings[LE-20]',
            'Рейтинг LE-5' => 'ratings[LE-5]',
            'Рейтинг LM' => 'ratings[LM]',
            'Рейтинг LV-100' => 'ratings[LV-100]',
            'Рейтинг LV-3' => 'ratings[LV-3]',
            'Рейтинг BD' => 'ratings[BD]',
            'Рейтинг WA' => 'ratings[WA]',
            'Выдержка' => 'aging',
            'Упаковка' => 'packing',
            'Аппелясьон' => 'appellation',
            'Тип ферментации' => 'fermentation',
            'Тип выдержки' => 'agingType',
        ];

        $csvColumnMapping = json_decode($importLog->getFieldsMapping(),true);

        if ('POST' === $request->getMethod()) {
            $csvColumnMapping = $request->request->get('csvColumnMapping', []);
            $csvColumnMapping = array_filter($csvColumnMapping);

            if (0 === count($csvColumnMapping)) {
                $this->addFlash('danger', 'Не выбрано ни одного соответствия!');
            } else {
                $importLog->setFieldsMapping(json_encode($csvColumnMapping));
                $this->em->persist($importLog);
                $this->em->flush();

                $this->addFlash('success', 'Успешно обновлено');
            }
        }

        return $this->render('admin/import/step2.html.twig', [
            'importLog' => $importLog,
            'headerArr' => $headerArr,
            'exampleDataRow' => $exampleRow,
            'importFields' => $importFields,
            'csvColumnMapping' => $csvColumnMapping,
        ]);
    }

    /**
     * @Route("/backend/import/step3/{id}", name="backend_import_step3")
     */
    public function importStep3(ImportLog $importLog,
                                FileUploader $fileUploader): Response
    {
        $this->setStage($importLog, 3);

        /*
         * тут массив в формате номер колонки => имя поля в БД
         */
        $csvColumnMapping = json_decode($importLog->getFieldsMapping(), true);
        $csvColumnMapping = null === $csvColumnMapping ? [] : array_filter($csvColumnMapping);

        if (0 === count($csvColumnMapping)) {
            $this->addFlash('danger', 'Не выбрано ни одного соответствия!');
            return $this->redirectToRoute('backend_import_step2', ['id' => $importLog->getId()]);
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
        $handle = fopen($fileUploader->getUploadedCsvPath($importLog), 'r');

        $dataToReview = [];

        $i = 0;
        // надо подготовить данные для проверки перед отправкой в БД
        while (false !== $csvData = fgetcsv($handle, 4000, $importLog->getCsvDelimiter())) {
            $i++;
            if (1 === $i AND $importLog->isFileContainHeader()) {
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

        return $this->render('admin/import/step3.html.twig', [
            'importLog' => $importLog,
            'dataToReview' => $dataToReview,
        ]);
    } // step 3


    /**
     * @Route("/backend/import/step4/{id}", name="backend_import_step4")
     */
    public function importStep4(ImportLog $importLog,
                                FileUploader $fileUploader,
                                OfferFactory $offerFactory): Response
    {
        $this->setStage($importLog, 4);

        /*
         * тут массив в формате номер колонки => имя поля в БД
         */
        $csvColumnMapping = json_decode($importLog->getFieldsMapping());

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
        $handle = fopen($fileUploader->getUploadedCsvPath($importLog), 'r');

        $offers = [];

        $i = $created = $updated = $linked = 0;
        // надо подготовить данные для проверки перед отправкой в БД
        while (false !== $csvData = fgetcsv($handle, 4000, $importLog->getCsvDelimiter())) {
            $i++;
            if (1 === $i AND $importLog->isFileContainHeader()) {
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

                        if (is_string($index)) {
                            $valueParts[$index] = $csvData[$subCsvColNum];
                        } else {
                            $valueParts[] = $csvData[$subCsvColNum];
                        }
                    }
                    $valueParts = array_filter($valueParts);

                    if ('grapeSort' === $entityFieldName) {
                        $valueParts = $this->parceGrapeSorts($valueParts);
                    }

                    // todo: use ternary
                    if (0 !== count($valueParts)) {
                        $newRow[$entityFieldName] = json_encode($valueParts);
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

            $offer = $offerFactory->makeOffer(new OfferDTO($newRow, $importLog));
            if ($offer->getCreatedAt() === $offer->getUpdatedAt()) {
                $created++;
            } else {
                $updated++;
            }

            $linked = null !== $offer->getProduct() ? $linked + 1 : $linked;

            $offers[] = $offer;

        } // while read file

        $importLog->setNote("создано: {$created}, обновлено: {$updated}, связаны с карточками: {$linked}")
            ->setIsComplete(true);
        $this->setStage($importLog, 4); // save inside

        return $this->render('admin/import/step4.html.twig', [
            'importLog' => $importLog,
            'rows' => $offers,
        ]);
    } // step4

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
     * @Route("/backend/import/copy/{id}", name="backend_import_copy")
     */
    public function copy(ImportLog $importLog)
    {
        /** @var Admin $admin */
        $admin = $this->getUser();

        $clone = clone $importLog;
        $clone->setName('change me')
            ->setAdmin($admin)
            ->setStage(1)
            ->setIsComplete(false)
        ;

        $this->em->persist($clone);
        $this->em->flush();

        return $this->redirectToRoute('backend_import_step1', ['id' => $clone->getId()]);
    }

    private function setStage(ImportLog $importLog, int $stage)
    {
        $importLog->setStage($stage);
        $this->em->persist($importLog);
        $this->em->flush();
    }
}
