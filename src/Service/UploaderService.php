<?php

namespace App\Service;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Visibility;

class UploaderService {
    private $targetDirectory;
    private $slugger;
    private $filesystem;
    private $publicAssetBaseUrl;

    public function __construct($targetDirectory, SluggerInterface $slugger, FilesystemOperator $filesystem, string $publicAssetBaseUrl)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->publicAssetBaseUrl = $publicAssetBaseUrl;
    }

    public function upload(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        /*try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }*/
        $path = '/files/'.$fileName;
        $stream = fopen($file->getPathname(), 'r');
        $result = $this->filesystem->writeStream($path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function getPublichPath(string $filename): string {
        return $this->publicAssetBaseUrl . '/files/' . $filename;
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {

        $resource = $this->filesystem->readStream($path);
        if ($resource === false) {
            throw new \Exception(sprintf('Error opening stream for "%s"', $path));
        }
        return $resource;
    }
}
