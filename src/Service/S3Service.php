<?php


namespace App\Service;

use Aws\S3\S3Client;



final class S3Service
{
    private $s3Host;
    private $s3Version;
    private $s3Bucket;
    private $s3Region;
    private $s3AccessKey;
    private $s3SecretKey;
    private $s3;

    public function __construct(string $s3Host,
                                string $s3Version,
                                string $s3Bucket,
                                string $s3Region,
                                string $s3AccessKey,
                                string $s3SecretKey)
    {
        $this->s3Host = $s3Host;
        $this->s3Version = $s3Version;
        $this->s3Bucket = $s3Bucket;
        $this->s3Region = $s3Region;
        $this->s3AccessKey = $s3AccessKey;
        $this->s3SecretKey = $s3SecretKey;

        $this->s3 = new S3Client([
            'version' 	=> $this->s3Version,
            'region'  	=> $this->s3Region,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'	=> $this->s3AccessKey,
                'secret' => $this->s3SecretKey,
            ],
            'endpoint' => $this->s3Host
        ]);
    }

    public function getBucketFiles(string $bucketName): array
    {
        $array = [];
//        $bucket = $this->s3->getBu1
        $objects = $this->s3->getIterator('ListObjects', array(
            'Bucket' => $bucketName
        ));

        foreach ($objects as $object) {
            $array[] = $bucketName . '/' . $object['Key'];
        }

        return $array;
    }

    public function listBuckets(): \Aws\Result
    {
        return $this->s3->listBuckets();
    }

    public function createBucket(string $name): \Aws\Result
    {
        return $this->s3->createBucket([
            'Bucket' => $name,
        ]);
    }

}