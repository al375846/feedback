<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Category;
use App\Entity\Feedback;
use App\Service\UploaderService;
use App\Entity\User;
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
use Symfony\Component\HttpFoundation\HeaderUtils;

class PublicationController extends AbstractController
{
    #[Route('/api/publication', name: 'publication_post', methods: ['POST'])]
    /**
     * @Route("/api/publication", name="publication_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publication", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="userdata", type="object",
     *              @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function postPublication(Request $request): Response
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
        $publication = $serializer->deserialize($request->getContent(), Publication::class, 'json');

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get category
        $catName = $publication->getCategory()->getName();
        $category = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$catName]);
        if ($category == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }
        $publication->setCategory($category);

        //Get apprentice
        $user = $this->getUser();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['userdata'=>$user]);
        $publication->setApprentice($apprentice);

        //Save publication
        $em->persist($publication);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($publication, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/publication', name: 'publication_get', methods: ['GET'])]
    /**
     * @Route("/api/publication", name="publication_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all publications",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publications", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="userdata", type="object",
     *              @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     */
    public function getPublications(): Response {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get publications
        $publications = $this->getDoctrine()->getRepository(Publication::class)->findAll();

        //Serialize the response data
        $data = $serializer->serialize($publications, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publications'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/publication/{id}', name: 'publication_get_id', methods: ['GET'])]
    /**
     * @Route("/api/publication/{id}", name="publication_get_id", methods={"GET"})
     * @OA\Response(response=200, description="Gets a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publication", type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="userdata", type="object",
     *              @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     */
    public function getPublication($id): Response {
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
            return new JsonResponse($response,404);
        }

        //Serialize the response data
        $data = $serializer->serialize($publication, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response, 200);
    }
/*
    #[Route('/api/publication/file/{filename}', name: 'publication_get_file', methods: ['GET'])]
    /**
     * @Route("/api/publication/file/{filename}", name="publication_get_file", methods={"GET"})
     * @OA\Response(response=200, description="Gets a file from a publication",
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
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     *//*
    public function getPublicationFile($filename, S3Client $s3Client) {
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

    #[Route('/api/publication/{id}/file', name: 'publication_post_file', methods: ['POST'])]
    /**
     * @Route("/api/publication/{id}/file", name="publication_post_file", methods={"POST"})
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
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     */
    public function postPublicationFile($id, Request $request, UploaderService $uploaderService): Response {
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
        $images = $publication->getImages();
        $document = $publication->getDocument();
        $video = $publication->getVideo();
        foreach($request->files->getIterator() as $file) {
            $filename = $uploaderService->upload($file);
            $arrayfile = explode(".", $filename);
            $extension = $arrayfile[count($arrayfile) - 1];
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
        $publication->setVideo($video);
        $publication->setDocument($document);
        $publication->setImages($images);

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

    #[Route('/api/publication/{id}/feedback', name: 'feedback_publication_get', methods: ['GET'])]
    /**
     * @Route("/api/publication/{id}/feedback", name="feedback_publication_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all feedbacks from a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedbacks", type="array", @OA\Items(type="object",
     *     @OA\Property(property="id", type="string"),
     *     @OA\Property(property="exepert", type="object",
     *          @OA\Property(property="userdata", type="object",
     *          @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getPublicationFeedback($id): Response
    {
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

        //Get publication
        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }
        $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['publication' => $publication]);

        //Serialize the response data
        $data = $serializer->serialize($feedbacks, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response=array('feedbacks'=>json_decode($data));

        return new JsonResponse($response,200);
    }
}
