<?php


namespace App\Service;


use App\DTO\ProductDTO;
use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Entity\Food;
use App\Entity\Product;
use App\Entity\Vendor;
use App\Repository\CountryRegionRepository;
use App\Repository\CountryRepository;
use App\Repository\FoodRepository;
use App\Repository\ProductRepository;
use App\Repository\VendorRepository;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;

final class ProductFactory
{
    private $em;
    private $productRepository;
    private $countryRepository;
    private $regionRepository;
    private $vendorRepository;
    private $foodRepository;

    public function __construct(EntityManagerInterface $em,
                                ProductRepository $productRepository,
                                CountryRepository $countryRepository,
                                CountryRegionRepository $regionRepository,
                                VendorRepository $vendorRepository,
                                FoodRepository $foodRepository)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
        $this->countryRepository = $countryRepository;
        $this->regionRepository = $regionRepository;
        $this->vendorRepository = $vendorRepository;
        $this->foodRepository = $foodRepository;
    }

    public function makeProduct(ProductDTO $dto): Product
    {
        $product = $this->productRepository->findOneBy(['productCode' => $dto->productCode]);

        if (null === $product) {
            $product = new Product();
        }

        // update product attributes
        $product->setSupplier($dto->supplier);
        $product->setCategory($dto->category);
        $product->setImport($dto->importLog);
        $product->setName($dto->name);
        $product->setColor($dto->color);
        $product->setType($dto->type);
        $product->setPrice((float)$dto->price);
        $product->setVolume((float)$dto->volume);
        $product->setGrapeSort($dto->grapeSort);
        $product->setAlcohol((float)$dto->alcohol);
        $product->setYear((int)$dto->year);
        $product->setServeTemperature($dto->serveTemperature);
        $product->setDecantation((bool)$dto->decantation);
        $product->setRatings($dto->ratings);
        $product->setAging($dto->aging);
        $product->setAgingType($dto->agingType);
        $product->setPacking($dto->packing);
        $product->setAppellation($dto->appellation);
        $product->setFermentation($dto->fermentation);

        if (empty($product->getSlug())) {
            $product->setSlug($this->makeSlug($product));
        }

        if (empty($product->getProductCode())) {
            $product->setProductCode(rand(1000000, 9999999));
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
            $product->setCountry($country);
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
            $product->setRegion($region);
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
        $product->setVendor($vendor);

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
                $product->addFood($food);
            }
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    private function makeSlug(Product $product)
    {
        $slug = Slugger::urlSlug($product->getName(), array('transliterate' => true));

        while($this->productRepository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

}