<?php

namespace App\Service;

use App\Entity\EventPic;
use App\Entity\ImportLog;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Vendor;
use App\Utils\Slugger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ramsey\Uuid\Uuid;


class FileUploader
{
    private $uploadsDirectory;
    private $productPicsSubDirectory;
    private $vendorLogoSubDirectory;
    private $vendorPicsSubDirectory;
    private $eventPicsSubDirectory;
    private $importFilesDirectory;
    
    public function __construct(string $uploadsDirectory,
                                string $importFilesDirectory,
                                string $productPicsSubDirectory,
                                string $vendorLogoSubDirectory,
                                string $vendorPicsSubDirectory,
                                string $eventPicsSubDirectory)
    {
        $this->eventPicsSubDirectory = $eventPicsSubDirectory;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->productPicsSubDirectory = $productPicsSubDirectory;
        $this->vendorLogoSubDirectory = $vendorLogoSubDirectory;
        $this->vendorPicsSubDirectory = $vendorPicsSubDirectory;
        $this->importFilesDirectory = $importFilesDirectory;
    }

    // todo:
    public function removeEventPic(EventPic $eventPic)
    {
        $fileName = sprintf('%s%s%s%s%s',
            $this->getUploadsDirectory(),
            DIRECTORY_SEPARATOR,
            $this->eventPicsSubDirectory,
            DIRECTORY_SEPARATOR,
            $eventPic->getPic()
        );

        if (is_file($fileName)) {
            unlink($fileName);
        } else {
            throw new \Exception($fileName);
        }
    }


    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();
        $file->move($this->getUploadsDirectory(), $fileName);

        return $fileName;
    }

    public function uploadEventPic(UploadedFile $file, string $prefix)
    {
        $fileName = sprintf('%s_%s.%s',
            $prefix,
            Uuid::uuid4(),
            $file->guessExtension()
        );

        $file->move(
            $this->getUploadsDirectory() . 
            DIRECTORY_SEPARATOR . 
            $this->eventPicsSubDirectory, $fileName
        );

        return $this->eventPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function uploadProductPic(UploadedFile $file, Product $product, string $prefix)
    {
        $fileName = sprintf('%s_%s.%s',
            $prefix,
            $product->getSlug(),
            $file->guessExtension()
        );

        $file->move($this->getUploadsDirectory() . DIRECTORY_SEPARATOR .
            $this->getProductPicsDirectory(), $fileName);

        return $this->getProductPicsDirectory() . DIRECTORY_SEPARATOR . $fileName;
    }

    public function uploadVendorLogo(UploadedFile $file, Vendor $vendor, string $prefix = 'logo')
    {
        $fileName = sprintf('%s_%s.%s',
            $prefix,
            $vendor->getSlug(),
            $file->guessExtension()
        );

        $file->move($this->getUploadsDirectory() . DIRECTORY_SEPARATOR .
            $this->vendorLogoSubDirectory, $fileName);

        return $this->vendorLogoSubDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function uploadVendorPic(UploadedFile $file, Vendor $vendor)
    {
        $fileName = sprintf('%s_%s.%s',
            $vendor->getSlug(),
            Uuid::uuid4(),
            $file->guessExtension()
        );

        $file->move($this->getUploadsDirectory() . DIRECTORY_SEPARATOR .
            $this->vendorPicsSubDirectory, $fileName);

        return $this->vendorPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function uploadImportCsv(UploadedFile $file, ImportLog $importLog)
    {
        $fileName = sprintf('%s_%s_%s_%s.%s',
            Slugger::urlSlug($importLog->getSupplier()),
            Slugger::urlSlug($importLog->getName()),
            $importLog->getCreatedAt()->format('Y-m-d'),
            $importLog->getId(),
            'csv' // $file->guessExtension()
        );

        $file->move($this->getImportFilesDirectory(), $fileName);

        return $fileName;
    }

    public function getUploadsDirectory(): string 
    {
        return $this->uploadsDirectory;
    }

    public function getProductPicsDirectory(): string 
    {
        return $this->productPicsSubDirectory;
    }

    public function getImportFilesDirectory(): string 
    {
        return $this->importFilesDirectory;
    }

    public function getUploadedCsvPath(ImportLog $importLog): string
    {
        return sprintf('%s/%s',
            $this->getImportFilesDirectory(),
            $importLog->getCsv()
        );
    }

}