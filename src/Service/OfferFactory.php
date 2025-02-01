<?php


namespace App\Service;


use App\DTO\OfferDTO;
use App\DTO\ProductDTO;
use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Entity\Food;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Vendor;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\FoodRepository;
use App\Repository\OfferRepository;
use App\Repository\ProductRepository;
use App\Repository\VendorRepository;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;

final class OfferFactory
{
    private $em;
    private $offerRepository;
    private $countryRepository;
    private $regionRepository;
    private $vendorRepository;
    private $foodRepository;

    /** @var WineColorService */
    private $wineColorService;

    /** @var WineSugarService */
    private $wineSugarService;

    public function __construct(EntityManagerInterface $em,
                                OfferRepository $offerRepository,
                                CountryRepository $countryRepository,
                                CountryRegionRepository $regionRepository,
                                VendorRepository $vendorRepository,
                                FoodRepository $foodRepository,
                                WineColorService $wineColorService,
                                WineSugarService $wineSugarService)
    {
        $this->em = $em;
        $this->offerRepository = $offerRepository;
        $this->countryRepository = $countryRepository;
        $this->regionRepository = $regionRepository;
        $this->vendorRepository = $vendorRepository;
        $this->foodRepository = $foodRepository;
        $this->wineColorService = $wineColorService;
        $this->wineSugarService = $wineSugarService;
    }

    public function emakeOffer(OfferDTO $dto): Offer
    {
        $offer = $this->offerRepository->findOneBy([
            'productCode' => $dto->productCode,
            'supplier' => $dto->supplier,
        ]);

        if (null === $offer) {
            $offer = new Offer();
        }

        // update product attributes
        $offer->setProductCode($dto->productCode);
        $offer->setSupplier($dto->supplier);
        $offer->setCategory($dto->category);
        $offer->setImport($dto->importLog);
        $offer->setName($dto->name);
        $offer->setColor($dto->color);
        $offer->setType($dto->type);
        $offer->setPrice((float)$dto->price);
        $offer->setVolume((float)$dto->volume);
        $offer->setGrapeSort($dto->grapeSort);
        $offer->setAlcohol((float)$dto->alcohol);
        $offer->setYear((int)$dto->year);
        $offer->setServeTemperature($dto->serveTemperature);
        $offer->setDecantation((bool)$dto->decantation);
        $offer->setRatings($dto->ratings);
        $offer->setAging($dto->aging);
        $offer->setAgingType($dto->agingType);
        $offer->setPacking($dto->packing);
        $offer->setAppellation($dto->appellation);
        $offer->setFermentation($dto->fermentation);

        if (empty($offer->getSlug())) {
            $offer->setSlug($this->makeSlug($offer));
        }

        if (empty($offer->getProductCode())) {
            $offer->setProductCode(rand(100000000, 999999999));
        }

        $country = null;
        // set county
        if (!empty($dto->country)) {
            $country = $this->countryRepository->findOneBy(['name' => $dto->country]);
            if (null === $country) {
                $country = new Country();
                $country->setName($dto->country);

                $this->em->persist($country);
                $this->em->flush();
            }
            $offer->setCountry($country);
        }

        // set region
        if ($country instanceof Country and !empty($dto->region)) {
            $region = $this->regionRepository->findOneBy([
                'name' => $dto->region,
                'country' => $country
            ]);

            if (null === $region) {
                $region = new CountryRegion();
                $region->setName($dto->name);
                $region->setCountry($country);

                $this->em->persist($region);
                $this->em->flush();
            }
            $offer->setRegion($region);
        }

        // set vendor
        $vendor = $this->vendorRepository->findOneBy(['name' => $dto->vendorName]);
        if (null === $vendor) {
            $vendor = new Vendor();
            $vendor->setName($dto->vendorName);
            $vendor->setWebsite($dto->vendorUrl);

            $this->em->persist($vendor);
            $this->em->flush();
        }
        $offer->setVendor($vendor);

        // set foods
        $foods = json_decode($dto->foods);
        if (is_array($foods)) {
            foreach ($foods as $food) {
                $food = $this->foodRepository->findOneBy(['name' => $food]);
                if (null === $food) {
                    $food = new Food();
                    $food->setName($food);

                    $this->em->persist($food);
                    $this->em->flush();
                }
                $offer->addFood($food);
            }
        }

        // todo: понять почему таймштамп не ставится
        $offer->setUpdatedAt(new \DateTime('now'));

        $this->em->persist($offer);
        $this->em->flush();

        return $offer;
    }

    private function makeSlug(Offer $offer): string
    {
        $slug = Slugger::urlSlug($offer->getName(), array('transliterate' => true));

        while($this->offerRepository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

}