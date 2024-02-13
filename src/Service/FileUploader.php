<?php

namespace App\Service;

use App\Entity\ImportLog;
use App\Entity\Product;
use App\Utils\Slugger;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $uploadsDirectory;
    private $productPicsSubDirectory;
    private $importFilesDirectory;
    
    public function __construct(string $uploadsDirectory,
                                string $productPicsSubDirectory,
                                string $importFilesDirectory)
    {
        $this->uploadsDirectory = $uploadsDirectory;
        $this->productPicsSubDirectory = $productPicsSubDirectory;
        $this->importFilesDirectory = $importFilesDirectory;
    }


    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();
        $file->move($this->getUploadsDirectory(), $fileName);

        return $fileName;
    }

    public function uploadProductPic(UploadedFile $file, Product $product, string $prefix)
    {
        $fileName = sprintf('%s_%s.%s',
            $prefix,
            $product->getSlug(),
            $file->guessExtension()
        );

        $file->move($this->getUploadsDirectory() . DIRECTORY_SEPARATOR . $this->getProductPicsDirectory(), $fileName);

        return $this->getProductPicsDirectory() . DIRECTORY_SEPARATOR . $fileName;
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