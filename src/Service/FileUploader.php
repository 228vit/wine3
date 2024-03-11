<?php

namespace App\Service;

use App\Entity\EventPic;
use App\Entity\ImportLog;
use App\Entity\Product;
use App\Entity\User;
use App\Utils\Slugger;
//use App\Utils\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ramsey\Uuid\Uuid;


class FileUploader
{
    private $eventsDirectory;
    private $uploadsDirectory;
    private $productPicsSubDirectory;
    private $importFilesDirectory;
    
    public function __construct(string $eventsDirectory,
                                string $uploadsDirectory,
                                string $productPicsSubDirectory,
                                string $importFilesDirectory)
    {
        $this->eventsDirectory = $eventsDirectory;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->productPicsSubDirectory = $productPicsSubDirectory;
        $this->importFilesDirectory = $importFilesDirectory;
    }

    // todo:
    public function removeUserAvatar(User $user)
    {
        $fileName = sprintf('%s%s%s%s%s',
            $this->getUploadsDirectory(),
            DIRECTORY_SEPARATOR,
            $this->getUserPicsSubDirectory(),
            DIRECTORY_SEPARATOR,
            $user->getPic()
        );

        if (is_file($fileName)) {
            unlink($fileName);
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

        $file->move($this->getUploadsDirectory() . DIRECTORY_SEPARATOR . $this->getProductPicsDirectory(), $fileName);

        return $this->getProductPicsDirectory() . DIRECTORY_SEPARATOR . $fileName;
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