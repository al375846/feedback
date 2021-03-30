<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Service\UploaderService;
use App\Entity\Publication;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
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

class FileController extends AbstractController
{

    /**
     * @var UploaderService
     */
    private UploaderService $uploader;

    public function __construct(UploaderService $uploaderService)
    {
        $this->uploader = $uploaderService;
    }

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
    public function getFeedbackFile($filename): Response {
        //Ask for the file
        $result = $this->uploader->getFile($filename);
        if ($result == null) {
            $response=array('error'=>'File not found');
            return new JsonResponse($response,404);
        }
        $mime = $result[0];
        $disposition = $result[1];
        $stream = $result[2];

        //Create the response
        $response = new StreamedResponse(function() use ($stream) {
            $outputStream = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $outputStream);
        });
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/api/file/feedback/{id}', name: 'feedback_file_post', methods: ['POST'])]
    /**
     * @Route("/api/file/feedback/{id}", name="feedback_file_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a file to feedbacks",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string"))
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *     @OA\Property(property="video", type="string", format="binary"),
     *     @OA\Property(property="document", type="string", format="binary"),
     *     @OA\Property(property="image", type="string", format="binary")
     *     )))
     * @OA\Tag(name="Files")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postFeedbackFile($id, Request $request): Response {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get feedback
        $feedback = $doctrine->getRepository(Feedback::class)->find($id);
        if ($feedback == null) {
            $response=array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Upload files
        $result = $this->uploadPrepare(
            $feedback->getDocument(),
            $feedback->getVideo(),
            $feedback->getImages(),
            $request->files->getIterator()
        );
        $feedback->setVideo($result[1]);
        $feedback->setDocument($result[0]);
        $feedback->setImages($result[2]);

        //Save feedback
        $em->persist($feedback);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($feedback, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['video', 'document', 'images']
        ]);

        //Create the response
        $response=array('feedback'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/file/publication/{id}', name: 'publication_file_post', methods: ['POST'])]
    /**
     * @Route("/api/file/publication/{id}", name="publication_file_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a file to publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publication", type="object",
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string"))
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *     @OA\Property(property="video", type="string", format="binary"),
     *     @OA\Property(property="document", type="string", format="binary"),
     *     @OA\Property(property="image", type="string", format="binary")
     *     )))
     * @OA\Tag(name="Files")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postPublicationFile($id, Request $request): Response {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get publication
        $publication = $this->getDoctrine()->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response, 404);
        }

        //Upload files
        $result = $this->uploadPrepare(
            $publication->getDocument(),
            $publication->getVideo(),
            $publication->getImages(),
            $request->files->getIterator()
        );
        $publication->setVideo($result[1]);
        $publication->setDocument($result[0]);
        $publication->setImages($result[2]);

        $em = $this->getDoctrine()->getManager();
        $em->persist($publication);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($publication, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['video', 'document', 'images']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    private function uploadPrepare($document, $video, $images, $files): array
    {
        foreach($files as $file) {
            $filename = $this->uploader->upload($file);
            $array = explode(".", $filename);
            $extension = $array[count($array) - 1];
            if ($extension == "pdf") {
                $document[count($document)] = $filename;
            }
            elseif ($extension == "mp4") {
                $video[count($video)] = $filename;
            }
            elseif ($extension == "jpg" or $extension == "jpeg" or $extension == "png") {
                $images[count($images)] = $filename;
            }
        }

        return [$document, $video, $images];
    }
}
