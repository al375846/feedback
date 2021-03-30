<?php

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploaderService {
    private SluggerInterface $slugger;
    private FilesystemOperator $filesystem;
    private string $publicAssetBaseUrl;
    /**
     * @var string[]
     */
    private array $mimes;
    /**
     * @var S3Client
     */
    private S3Client $s3Client;

    public function __construct(SluggerInterface $slugger, FilesystemOperator $filesystem, string $publicAssetBaseUrl, S3Client $s3Client)
    {
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->publicAssetBaseUrl = $publicAssetBaseUrl;
        $this->mimes = array(
            "pdf"  => "application/pdf",
            "jpeg"  => "image/jpeg",
            "jpg"  => "image/jpg",
            "png"  => "image/png",
            "mp4"  => "video/mp4",
        );
        $this->s3Client = $s3Client;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $path = '/files/'.$fileName;
        $stream = fopen($file->getPathname(), 'r');
        try {
            $this->filesystem->writeStream($path, $stream);
        } catch (FilesystemException $e) {
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $fileName;
    }

    public function getPublichPath(string $filename): string {
        return $this->publicAssetBaseUrl . '/files/' . $filename;
    }

    public function getFile($filename): ?array
    {

        //Prepare the request
        $file = explode(".", $filename);
        $extension = $file[count($file) - 1];
        $mime = $this->mimes[$extension];
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );

        //Get the file
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => 'feedback-uji',
                'Key' => 'files/'. $filename,
                'ResponseContentType' => $mime,
                'ResponseContentDisposition' => $disposition,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        //Get the resource
        $stream = $result['Body']->detach();

        return [$mime, $disposition, $stream];
    }

}
