<?php


namespace App\Service;


use App\Entity\Vendor;
use App\Repository\ProductRepository;

class ProductDataService
{
    private $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBottleVolumes(): array
    {
        $volumes = [];
        foreach ($this->repository->getBottleVolumes() as $item) {
            $volumes[] = $item['volume'];
        }

        return $volumes;
    }

    public function getVendorBottleVolumes(Vendor $vendor): array
    {
        $volumes = [];
        foreach ($this->repository->getVendorBottleVolumes($vendor) as $item) {
            $volumes[] = $item['volume'];
        }

        return $volumes;
    }

    public function getBottleVolumesReversed(): array
    {
        $volumes = [];
        foreach ($this->getBottleVolumes() as $key => $bottleVolume) {
            $volumes[$bottleVolume] = $key;
        }

        return $volumes;
    }

    public function getYears(): array
    {
        $volumes = [];
        foreach ($this->repository->getYears() as $item) {
            $volumes[] = $item['year'];
        }

        return $volumes;
    }

    public function getVendorYears(Vendor $vendor): array
    {
        $volumes = [];
        foreach ($this->repository->getVendorYears($vendor) as $item) {
            $volumes[] = $item['year'];
        }

        return $volumes;
    }

    public function getAlcohol(): array
    {
        $volumes = [];
        foreach ($this->repository->getAlcohol() as $item) {
            $volumes[] = $item['alcohol'];
        }

        return $volumes;
    }

}