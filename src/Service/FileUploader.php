<?php

namespace App\Service;

use App\Entity\EventPic;
use App\Entity\ImportLog;
use App\Entity\Product;
use App\Entity\Vendor;
use App\Utils\Slugger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileUploader
{
    private $uploadsDirectory;
    private $productPicsSubDirectory;
    private $vendorLogoSubDirectory;
    private $vendorPicsSubDirectory;
    private $eventPicsSubDirectory;
    private $importFilesDirectory;
    private $httpClient;

    public function __construct(string $uploadsDirectory,
                                string $importFilesDirectory,
                                string $productPicsSubDirectory,
                                string $vendorLogoSubDirectory,
                                string $vendorPicsSubDirectory,
                                string $eventPicsSubDirectory,
                                HttpClientInterface $httpClient)
    {
        $this->eventPicsSubDirectory = $eventPicsSubDirectory;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->productPicsSubDirectory = $productPicsSubDirectory;
        $this->vendorLogoSubDirectory = $vendorLogoSubDirectory;
        $this->vendorPicsSubDirectory = $vendorPicsSubDirectory;
        $this->importFilesDirectory = $importFilesDirectory;
        $this->httpClient = $httpClient;
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

    public function grabOfferPic(string $url, int $rotationAngle = 270)
    {
        $info = pathinfo($url);
        $extension = strtolower($info['extension']);

        switch($extension) {
            case "jpg":
                $img = imagecreatefromjpeg($url);
                break;
            case "jpeg":
                $img = imagecreatefromjpeg($url);
                break;
            case "png":
                $img = imagecreatefrompng($url);
                break;
            case "gif":
                $img = imagecreatefromgif($url);
                break;
            default:
                $img = imagecreatefromjpeg($url);
        }

        $img = imagerotate($img, $rotationAngle, 0);

        $fileName = 'offer_'.rand(100000, 999999).'.'.$extension;
        $path = $this->getUploadsDirectory() . DIRECTORY_SEPARATOR .$this->productPicsSubDirectory
            . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($path)) {
            $f = fopen($path, 'w');
            fclose($f);
        }

        imagejpeg($img, $path);

        return $this->productPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function makePng(string $url, int $rotationAngle = 270)
    {
        $info = pathinfo($url);
        $extension = strtolower($info['extension']);

        switch($extension) {
            case "jpg":
                $image = imagecreatefromjpeg($url);
                break;
            case "jpeg":
                $image = imagecreatefromjpeg($url);
                break;
            case "png":
                $image = imagecreatefrompng($url);
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

        $bgColor = imagecolorat($image, 0, 0);

        $width = imagesx($image);
        $height = imagesy($image);

        $newImage = imagecreatetruecolor($width, $height);
        imagesavealpha($newImage, true);
        $transparency = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparency);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                if (imagecolorat($image, $x, $y) !== $bgColor) {
                    imagesetpixel($newImage, $x, $y, imagecolorat($image, $x, $y));
                }
            }
        }

        $fileName = 'offer_'.rand(100000, 999999).'.'.$extension;
        $path = $this->getUploadsDirectory() . DIRECTORY_SEPARATOR .$this->productPicsSubDirectory
            . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($path)) {
            $f = fopen($path, 'w');
            fclose($f);
        }

        imagepng($newImage, $path);
        imagedestroy($image);
        imagedestroy($newImage);


        return $this->productPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

}