<?php

namespace App\Controller;

use App\Entity\Expert;
use App\Entity\Feedback;
use App\Service\UploaderService;
use App\Entity\User;
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
use App\Entity\Publication;
use Symfony\Component\HttpFoundation\HeaderUtils;

class FeedbackController extends AbstractController
{
    #[Route('/api/feedback/publication/{id}', name: 'feedback_post', methods: ['POST'])]
    /**
     * @Route("/api/feedback/publication/{id}", name="feedback_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="id", type="string"),
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postFeedback($id, Request $request): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserialize to obtain object data
        $user = $this->getUser();
        $feedback = $serializer->deserialize($request->getContent(), Feedback::class, 'json');

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get publication
        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }
        $feedback->setPublication($publication);

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);
        $feedback->setExpert($expert);

        //Save the feedback
        $em->persist($feedback);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($feedback, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks']
        ]);

        //Create the response
        $response=array('feedback'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/feedback', name: 'feedback_get', methods: ['GET'])]
    /**
     * @Route("/api/feedback", name="feedback_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all feedbacks",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedbacks", type="array", @OA\Items(
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     */
    public function getFeedbacks(): Response {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get feedbacks
        $feedbacks = $this->getDoctrine()->getRepository(Feedback::class)->findAll();

        //Serialize the response data
        $data = $serializer->serialize($feedbacks, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response=array('feedbacks'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/feedback/{id}', name: 'feedback_get_id', methods: ['GET'])]
    /**
     * @Route("/api/feedback/{id}", name="feedback_get_id", methods={"GET"})
     * @OA\Response(response=200, description="Gets a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getFeedback($id): Response {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get feedback
        $feedback = $this->getDoctrine()->getRepository(Feedback::class)->find($id);
        if ($feedback == null) {
            $response=array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Serialize the response data
        $data = $serializer->serialize($feedback, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks']
        ]);

        //Create the response
        $response=array('feedback'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/feedback/{id}/file', name: 'feedback_post_file', methods: ['POST'])]
    /**
     * @Route("/api/feedback/{id}/file", name="feedback_post_file", methods={"POST"})
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
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @param UploaderService $uploaderService
     * @return Response
     */
    public function postFeedbackFile($id, Request $request, UploaderService $uploaderService): Response {
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
        $images = $feedback->getImages();
        $document = $feedback->getDocument();
        $video = $feedback->getVideo();
        foreach($request->files->getIterator() as $file) {
            $filename = $uploaderService->upload($file);
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
        $feedback->setVideo($video);
        $feedback->setDocument($document);
        $feedback->setImages($images);

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
/*
    #[Route('/api/feedback/file/{filename}', name: 'feedback_get_file', methods: ['GET'])]
    /**
     * @Route("api/feedback/file/{filename}", name="feedback_get_file", methods={"GET"})
     * @OA\Response(response=200, description="Gets a file from a feedback",
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
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     *//*
    public function getFeedbackFile($filename, S3Client $s3Client) {
        $tipos = array(
            "pdf"  => "application/pdf",
            "jpeg"  => "image/jpeg",
            "jpg"  => "image/jpg",
            "png"  => "image/png",
            "mp4"  => "video/mp4",
        );
        $arrayfile = explode(".", $filename);
        $extension = $arrayfile[count($arrayfile) - 1];
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $result = $s3Client->getObject([
            'Bucket' => 'feedback-uji',
            'Key' => 'files/'. $filename,
            'ResponseContentType' => $tipos[$extension],
            'ResponseContentDisposition' => $disposition,
        ]);

        $stream = $result['Body']->detach();

        $response = new StreamedResponse(function() use ($stream) {
            $outputStream = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $outputStream);
            ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', $tipos[$extension]);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }*/
}
