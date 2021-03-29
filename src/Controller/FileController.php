<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Category;
use App\Entity\Expert;
use App\Entity\Feedback;
use App\Entity\Valoration;
use App\Service\UploaderService;
use App\Entity\User;
use Aws\S3\S3Client;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use App\Entity\Publication;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;

class FileController extends AbstractController
{
    #[Route('/api/file/{filename}', name: 'get_file', methods: ['GET'])]
    /**
     * @Route("api/file/{filename}", name="get_file", methods={"GET"})
     * @OA\Response(response=200, description="Gets a file from a feedback or a publication",
     *     @OA\MediaType(mediaType="application/pdf",
     *     @OA\Schema(@OA\Property(property="document", type="string", format="binary"))),
     *     @OA\MediaType(mediaType="image/png",
     *     @OA\Schema(@OA\Property(property="image", type="string", format="binary"))),
     *     @OA\MediaType(mediaType="image/jpg",
     *     @OA\Schema(@OA\Property(property="image", type="string", format="binary"))),
     *     @OA\MediaType(mediaType="image/jpeg",
     *     @OA\Schema(@OA\Property(property="image", type="string", format="binary"))),
     *     @OA\MediaType(mediaType="video/mp4",
     *     @OA\Schema(@OA\Property(property="video", type="string", format="binary"))),
     * )
     * @OA\Tag(name="Files")
     * @Security(name="Bearer")
     * @param $filename
     * @param S3Client $s3Client
     * @return Response
     */
    public function getFeedbackFile($filename, S3Client $s3Client): Response {
        //Prepare the request
        $mimes = array(
            "pdf"  => "application/pdf",
            "jpeg"  => "image/jpeg",
            "jpg"  => "image/jpg",
            "png"  => "image/png",
            "mp4"  => "video/mp4",
        );
        $file = explode(".", $filename);
        $extension = $file[count($file) - 1];
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );

        //Get the file
        try {
        $result = $s3Client->getObject([
            'Bucket' => 'feedback-uji',
            'Key' => 'files/'. $filename,
            'ResponseContentType' => $mimes[$extension],
            'ResponseContentDisposition' => $disposition,
        ]);
        } catch (\Throwable $e) {
            $response=array('error'=>'File not found');
            return new JsonResponse($response,404);
        }

        //Get the resource
        $stream = $result['Body']->detach();

        //Create the response
        $response = new StreamedResponse(function() use ($stream) {
            $outputStream = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $outputStream);
        });
        $response->headers->set('Content-Type', $mimes[$extension]);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
