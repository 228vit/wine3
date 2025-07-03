<?php

namespace App\Service;

use App\Entity\EventPic;
use App\Entity\ImportLog;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Vendor;
use App\Utils\Slugger;
use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\ObjectUploader;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem as FlysystemFilesystem;

class FileUploader
{
    private $uploadsDirectory;
    private $productPicsSubDirectory;
    private $vendorLogoSubDirectory;
    private $vendorPicsSubDirectory;
    private $eventPicsSubDirectory;
    private $importFilesDirectory;
    private $httpClient;
    private $cacheManager;

    public function __construct(string $uploadsDirectory,
                                string $importFilesDirectory,
                                string $productPicsSubDirectory,
                                string $vendorLogoSubDirectory,
                                string $vendorPicsSubDirectory,
                                string $eventPicsSubDirectory,
                                CacheManager $cacheManager,
                                HttpClientInterface $httpClient)
    {
        $this->eventPicsSubDirectory = $eventPicsSubDirectory;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->productPicsSubDirectory = $productPicsSubDirectory;
        $this->vendorLogoSubDirectory = $vendorLogoSubDirectory;
        $this->vendorPicsSubDirectory = $vendorPicsSubDirectory;
        $this->importFilesDirectory = $importFilesDirectory;
        $this->cacheManager = $cacheManager;
        $this->httpClient = $httpClient;
    }

    public function saveOfferPicToS3(Offer $offer, Product $product): ?string
    {
        if (empty($offer->getPicUrl())) {
            throw new \Exception('Empty Pic URL');
        }

        $parts = explode('.', $offer->getPicUrl());
        $ext = 'jpg';
        if (is_array($parts)) {
            $ext = end($parts);
        }

        $s3 = new S3Client([
            'version' 	=> 'latest',
            'region'  	=> 'default',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'	=> '6VHWXYOCRNJMZO6X116T',
                'secret' => 'l5dIzPCfXTDeCdlXFUlmTPBun18hnPsxuroGSYh6',
            ],
            'endpoint' => 'https://s3.regru.cloud/'
        ]);

        $source = fopen($offer->getPicUrl(), 'rb');

        $fileName = Slugger::urlSlug(sprintf('%s-%s-%s.%s',
            $offer->getSupplier()->getName(),
            $product->getId(),
            $offer->getName(),
            $ext
        ));

        $uploader = new ObjectUploader(
            $s3,
            'wine',
            $fileName,
            $source
        );

        do {
            try {
                $result = $uploader->upload();
                if ($result["@metadata"]["statusCode"] == '200') {
//                    print('<p>File successfully uploaded to ' . $result["ObjectURL"] . '.</p>');
                    return $result["ObjectURL"];
                }

            } catch (MultipartUploadException $e) {
                rewind($source);
                $uploader = new MultipartUploader($s3, $source, [
                    'state' => $e->getState(),
                ]);
            }
        } while (!isset($result));

        fclose($source);

        return null;
    }

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

    public function removeProductPics(Product $product)
    {
        if (null !== $product->getAnnouncePic()) {
            $this->cacheManager->remove('uploads' . DIRECTORY_SEPARATOR . $product->getAnnouncePic());
            echo "del announce thumb <br>";
        }
        if (null !== $product->getContentPic()) {
            $this->cacheManager->remove('uploads' . DIRECTORY_SEPARATOR . $product->getContentPic());
            echo "del content thumb <br>";
        }
        if (null !== $product->getExtraPic()) {
            $this->cacheManager->remove('uploads' . DIRECTORY_SEPARATOR . $product->getExtraPic());
            echo "del extra thumb <br>";
        }

        $fileName = sprintf('%s%s%s',
            $this->getUploadsDirectory(),
            DIRECTORY_SEPARATOR,
            $product->getAnnouncePic()
        );
        echo "announce pic $fileName <br>";
        if (is_file($fileName)) {
            unlink($fileName);
//            echo " deleted announce pic $fileName <br>";
        }

        $fileName = sprintf('%s%s%s',
            $this->getUploadsDirectory(),
            DIRECTORY_SEPARATOR,
            $product->getContentPic()
        );
        if (is_file($fileName)) unlink($fileName);

        $fileName = sprintf('%s%s%s',
            $this->getUploadsDirectory(),
            DIRECTORY_SEPARATOR,
            $product->getExtraPic()
        );
        if (is_file($fileName)) unlink($fileName);

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

    public function grabProductPic(string $url, Product $product)
    {
        $info = pathinfo($url);
        $extension = strtolower($info['extension']);

        $ext = 'jpg';
        switch($extension) {
            case "jpg":
                $img = imagecreatefromjpeg($url);
                break;
            case "jpeg":
                $img = imagecreatefromjpeg($url);
                break;
            case "png":
                $img = imagecreatefrompng($url);
                $ext = 'png';
                break;
            case "gif":
                $img = imagecreatefromgif($url);
                break;
            default:
                $img = imagecreatefromjpeg($url);
        }

        $fileName = 'product_'.$product->getId().'.'.$ext;

        $path = $this->getUploadsDirectory() . DIRECTORY_SEPARATOR .$this->productPicsSubDirectory
            . DIRECTORY_SEPARATOR . $fileName;
        // видимо надо создать пустой файл, что бы потом всё записалось
        if (!file_exists($path)) {
            $f = fopen($path, 'w');
            fclose($f);
        }

        if ('png' === $ext) {
            imagepng($img, $path);
        } else {
            imagejpeg($img, $path);
        }

        return $this->productPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
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

    public function makePng(string $url, string $id, int $rotationAngle = 270, $alpha = 20)
    {
        try {
            $info = pathinfo($url);
            $extension = strtolower($info['extension']);
            $isPng = false;

            switch ($extension) {
                case "png":
                    $isPng = true;
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

            $width = imagesx($image);
            $height = imagesy($image);

            $newImage = imagecreatetruecolor($width, $height);
            imagesavealpha($newImage, true);
            $transparency = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparency);

            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $colors = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    if ($colors['red'] <= 235 AND $colors['green'] <= 235 AND $colors['blue'] <= 235) {
                        $pixel = imagecolorat($image, $x, $y);
                        imagesetpixel($newImage, $x, $y, $pixel);
                    }

//                    if (!$isPng AND $colors['red'] >= 235) { // AND $colo
//                        $pixel = imagecolorallocatealpha(
//                            $newImage,
//                            255, 255, 255, $alpha);
//                        imagesetpixel($newImage, $x, $y, $pixel);
//                    } else {
//                        $pixel = imagecolorat($image, $x, $y);
//                        imagesetpixel($newImage, $x, $y, $pixel);
//                    }
                }
            }

            $fileName = 'offer_' . $id . '.png';
            $path = $this->getUploadsDirectory() . DIRECTORY_SEPARATOR . $this->productPicsSubDirectory
                . DIRECTORY_SEPARATOR . $fileName;

            if (!file_exists($path)) {
                $f = fopen($path, 'w');
                fclose($f);
            }

            imagepng($newImage, $path);
            imagedestroy($image);
            imagedestroy($newImage);


            return $this->productPicsSubDirectory . DIRECTORY_SEPARATOR . $fileName;
        } catch (\Exception $e) {
            return null;
        }
    }

}