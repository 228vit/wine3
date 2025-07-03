<?php

namespace App\Command;

use App\Entity\GrapeSort;
use App\Entity\GrapeSortAlias;
use App\Entity\ImportYml;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\ProductGrapeSort;
use App\Entity\ProductRating;
use App\Entity\Rating;
use App\Repository\AppellationRepository;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\OfferRepository;
use App\Repository\VendorRepository;
use App\Service\FileUploader;
use App\Service\WineColorService;
use App\Service\WineSugarService;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\ImportYmlRepository;

class YmlImportCommand extends Command
{
    private $em;
    private $io = null;
    private $importYmlRepository;
    private $vendorRepository;
    private $offerRepository;
    private $appellationRepository;
    private $countryRepository;
    private $regionRepository;
    private $uploadsPath;
    private $wineColorService;
    private $wineSugarService;
    private $fileUploader;
    /** @var ImportYml $importYml */
    private $importYml = null;
    private $vendors = [];
    private $countries = [];
    private $regions = [];
    private $appellations = [];

    public function __construct(EntityManagerInterface $entityManager,
                                ImportYmlRepository $importYmlRepository,
                                VendorRepository $vendorRepository,
                                OfferRepository $offerRepository,
                                AppellationRepository $appellationRepository,
                                CountryRegionRepository $regionRepository,
                                CountryRepository $countryRepository,
                                WineColorService $wineColorService,
                                WineSugarService $wineSugarService,
                                FileUploader $fileUploader,
                                string $localUploadsDirectory)
    {
        $this->importYmlRepository = $importYmlRepository;
        $this->vendorRepository = $vendorRepository;
        $this->offerRepository = $offerRepository;
        $this->appellationRepository = $appellationRepository;
        $this->countryRepository = $countryRepository;
        $this->regionRepository = $regionRepository;
        $this->uploadsPath = $localUploadsDirectory;
        $this->wineSugarService = $wineSugarService;
        $this->wineColorService = $wineColorService;
        $this->fileUploader = $fileUploader;

        $this->em = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('import:yml')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'YML Import ID')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset (default = 0)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit (default = 50)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');
        $offset = intval($input->getOption('offset'));
        $offset = empty($offset) ? 0 : $offset;
        $limit = $input->getOption('limit');
        $limit = empty($limit) ? 2 : $limit;
        $finishStep = $offset + $limit - 1; // 0+10

        /** @var ImportYml $importYml */
        $importYml = $this->importYml = $this->importYmlRepository->find($id);

        if (!$importYml) {
            $this->io->error('Wrong ID supported');
            return Command::FAILURE;
        }

        // grab YML to local PATH, to avoid re-query
        if (0 === $offset) {
            $ymlContent = file_get_contents($importYml->getUrl());
            $storedYmlPath = sprintf('%s%s.yml',
                $this->uploadsPath . DIRECTORY_SEPARATOR . 'yml' . DIRECTORY_SEPARATOR,
                Slugger::urlSlug($importYml->getSupplier()->getName().'-at-'.date('Y-m-d'))
            );

            $res = file_put_contents($storedYmlPath, $ymlContent);

            if (false === $res) {
                $this->io->success('YML file cannot be saved');
                return Command::FAILURE;
            }

            $importYml->setSavedYmlPath($storedYmlPath);
            $this->em->persist($importYml);
            $this->em->flush();
        }

        $this->countries = json_decode($importYml->getCountriesMapping(), true);
        $this->regions = json_decode($importYml->getRegionsMapping(), true);
        $this->appellations = json_decode($importYml->getAppellationsMapping(), true);
        $this->vendors = json_decode($importYml->getVendorsMapping(), true); // "Gaja" => "140"

        // load all rows
        $data = simplexml_load_file($importYml->getSavedYmlPath() ? $importYml->getSavedYmlPath() : $importYml->getUrl());

        $totalOffers = count($data->shop->offers->offer);
        $importYml->setTotalRows($totalOffers);

        $currentRow = 0;

        // loop rows
        foreach ($data->shop->offers->offer as $row) {
            // пропускаем обработанные строки
            if ($currentRow < $offset) {
                ++$currentRow;
                continue;
            }

            $offerId = strval($row->attributes()->id);
            $name = html_entity_decode(strval($row->name), ENT_QUOTES);

            $this->io->writeln("curr: $currentRow, finish: $finishStep, offset: $offset, id: $offerId, name: $name");

            if ($currentRow >= $finishStep) {
                $this->io->writeln('break loop');
                break;
            }

            $importYml->setCurrentRowYmlId($offerId);
            $importYml->setImportStatus(ImportYml::STATUS_START);

            try {

                $this->importOffer($row);

            } catch (\Exception $e) {
                $this->em->persist($importYml);
                $this->em->flush();

                $this->io->error($e->getMessage());
                $this->io->error($e->getTraceAsString());

                return Command::FAILURE;
            }

            $importYml->setImportStatus(ImportYml::STATUS_DATA_SAVED);

            $this->em->persist($importYml);
            $this->em->flush();

            $currentRow++;
        } // foreach offers

        if ($currentRow > $totalOffers) {
            $importYml->setImportStatus(ImportYml::STATUS_DONE);
            $importYml->setIsComplete(true);

            $this->em->persist($importYml);
            $this->em->flush();

            return Command::SUCCESS;
        }

        // call next

        return Command::SUCCESS;

        $script = sprintf('%s/bin/console import:yml --id=%s --offset=%s --limit=%s',
            $this->getParameter('kernel.project_dir'),
            $id,
            $offset,
            $limit
        );

        shell_exec(sprintf('%s > /dev/null 2>&1 &', $script));
    }

    private function importOffer(\SimpleXMLElement $row)
    {
        $offerId = strval($row->attributes()->id);
        $isActive = boolval($row->attributes()->available);
        $price = floatval($row->price);
        $name = html_entity_decode(strval($row->name), ENT_QUOTES);
        $barcode = isset($row->barcode) ? strval($row->barcode) : null;
        $vendorName = $this->getYmlParam($row, 'tovmarka');
        $picUrl = strval($row->picture);
        $description = html_entity_decode(strval($row->description), ENT_QUOTES);
        $categoryId = strval($row->categoryId); // country - region - appellation
        $appellation = null;
        $region = null;
        $country = null;
        $wineColor = $this->getYmlParam($row, 'typenom');
        $wineSugar = $this->getYmlParam($row, 'vidvina');
        $year = intval($this->getYmlParam($row, 'year'));
        $volume = floatval($this->getYmlParam($row, 'vol')); // 0.75l
        $alcohol = floatval($this->getYmlParam($row, 'degree')); // 0.75l

        $vendor = null;
        if (isset($vendors[$vendorName])) {
            $vendorId = $this->vendors[$vendorName];
            $vendor = $this->vendorRepository->find($vendorId);
        } //? $vendors[$vendorName] : null;

        $this->io->writeln("Offer: {$name}, ID: {$offerId}");
        /** @var Offer $offer */
        $offer = $this->offerRepository->findOneBy([
            'ymlId' => $offerId,
        ]);

        if ($offer) {
            // update only important fields
            $offer->setPrice($price)
                ->setPicUrl($picUrl)
                ->setIsActive($isActive)
                ->setVendor($vendor)
            ;
            $this->io->writeln("update offer: {$offer->getName()}");
        } else {
            if (isset($appellations[$categoryId])) {
                $appellationInDb = $this->appellationRepository->find($this->appellations[$categoryId]);
                if ($appellationInDb) {
                    $appellation = $appellationInDb;
                    $region = $appellationInDb->getCountryRegion();
                    $country = $appellationInDb->getCountry();
                }
            }

            if (isset($regions[$categoryId])) {
                $regionInDb = $this->regionRepository->find($this->regions[$categoryId]);
                if ($regionInDb) {
                    $region = $regionInDb;
                    $country = $regionInDb->getCountry();
                }
            }

            if (isset($countries[$categoryId])) {
                $countryInDb = $this->countryRepository->find($this->countries[$categoryId]);
                if ($countryInDb) {
                    $country = $countryInDb;
                }
            }
            $grapes = [];
            if (isset($row->grapeVarieties)) {
                foreach ($row->grapeVarieties->grape as $grape) {

                    $grapeName = strval($grape->attributes()->name);
                    $percentage = strval($grape->attributes()->percentage);
                    if (!empty($grapeName) AND !empty($percentage)) {
                        $grapes[$grapeName] = $percentage;
                    }
                }
            }

            $offer = (new Offer())
                ->setImportYml($this->importYml)
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
                ->setSupplier($this->importYml->getSupplier())
                ->setYear($year)
                ->setVolume($volume)
                ->setAlcohol($alcohol)
                ->setType($wineSugar)
                ->setColor($wineColor)
                ->setGrapeSort(json_encode($grapes))
                ->setPicUrl($picUrl)
            ;
        } // if offer

        $this->em->persist($offer);
        $this->em->flush();

        $product = $this->makeProduct($offer);

        $this->io->success('---');
        // todo: return something?
    } // func importOffer

    private function makeProduct(Offer $offer): Product
    {
        $grapeSortRepository = $this->em->getRepository(GrapeSort::class);
        $grapeSortAliasRepository = $this->em->getRepository(GrapeSortAlias::class);
        $productGrapeSortRepository = $this->em->getRepository(ProductGrapeSort::class);
        $ratingRepository = $this->em->getRepository(Rating::class);
        $productRepository = $this->em->getRepository(Product::class);
        $productRatingRepository = $this->em->getRepository(ProductRating::class);

        /** @var Product $product */
        $product = $productRepository->findOneByNameOrBarcode($offer->getName(), $offer->getBarcode());

        if ($product) {
            $this->io->writeln( $product->getId() . ' Product exist, link offer');
            $product->addOffer($offer)
                ->setIsActive($offer->getIsActive())
                ->setPrice($offer->getPrice())
                ->setVendor($offer->getVendor())
            ;
            $this->em->persist($product);
            $this->em->flush();

            return $product;
        }

        $this->io->writeln('Create Product, link offer');

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
            ->setWineColor($this->wineColorService->getWineColor($offer->getColor()))
            // wine sugar
            ->setType($offer->getType())
            ->setWineSugar($this->wineSugarService->getWineSugar($offer->getType()))

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

        $this->em->persist($product);
        $this->em->flush();

        if ($offer->getPicUrl()) {
            try {
                $picPathAbsolute = $this->fileUploader->saveOfferPicToS3($offer, $product);
                if ($picPathAbsolute) {
                    $product
                        ->setContentPic($picPathAbsolute)
                        ->setAnnouncePic($picPathAbsolute)
                    ;
                    $this->io->success('Saved Pic to S3: ' . $picPathAbsolute);
                }
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
                $this->io->error($e->getTraceAsString());
            }
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
                    /** @var GrapeSort $grapeSort */
                    $grapeSort = $alias->getParent();
                } else {
                    /** @var GrapeSort $grapeSort */
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
                /** @var Rating $rating */
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

    private function getYmlParam($row, $name = 'name')
    {
        foreach ($row->param as $param) {
            if ($param->attributes()->name == $name) {
                return trim(strval($param));
            }
        }

        return null;
    }
}
