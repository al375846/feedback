<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Service\SerializerService;
use App\Service\UploaderService;
use App\Entity\Publication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

class FileController extends AbstractController
{

    /**
     * @var UploaderService
     */
    private UploaderService $uploader;
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(UploaderService $uploaderService, SerializerService $serializerService)
    {
        $this->uploader = $uploaderService;
        $this->serializer = $serializerService->getSerializer();
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
     *     @OA\Property(property="documents", type="object",
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
    public function postFeedbackFile($id, Request $request): Response
    {
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
        $data = $this->serializer->serialize($feedback, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['video', 'document', 'images']
        ]);

        //Create the response
        $response=array('documents'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/file/publication/{id}', name: 'publication_file_post', methods: ['POST'])]
    /**
     * @Route("/api/file/publication/{id}", name="publication_file_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a file to publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="documents", type="object",
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
    public function postPublicationFile($id, Request $request): Response
    {
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
        $data = $this->serializer->serialize($publication, 'json', [
            AbstractNormalizer::ATTRIBUTES => ['video', 'document', 'images']
        ]);

        //Create the response
        $response=array('documents'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/file/{filename}/feedback/{id}', name: 'delete_feedback_file', methods: ['DELETE'])]
    /**
     * @Route("/api/file/{filename}/feedback/{id}", name="delete_feedback_file", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a file of a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Files")
     * @Security(name="Bearer")
     * @param $filename
     * @param $id
     * @return Response
     */
    public function deleteFeedbackFile($filename, $id): Response {
        //Get the feedback
        $feedback = $this->getDoctrine()->getRepository(Feedback::class)->find($id);
        if ($feedback == null) {
            $response=array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Delete files
        $result = $this->deletePrepare(
            $feedback->getDocument(),
            $feedback->getVideo(),
            $feedback->getImages(),
            $filename
        );

        //Check if the file has been deleted
        if (!$result[3]) {
            $response=array('error'=>'File not found');
            return new JsonResponse($response,404);
        }

        $feedback->setVideo($result[1]);
        $feedback->setDocument($result[0]);
        $feedback->setImages($result[2]);

        $em = $this->getDoctrine()->getManager();
        $em->persist($feedback);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }

    #[Route('/api/file/{filename}/publication/{id}', name: 'delete_publication_file', methods: ['DELETE'])]
    /**
     * @Route("/api/file/{filename}/publication/{id}", name="delete_publication_file", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a file of a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Files")
     * @Security(name="Bearer")
     * @param $filename
     * @param $id
     * @return Response
     */
    public function deletePublicationFile($filename, $id): Response {
        //Get the publication
        $publication = $this->getDoctrine()->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Delete files
        $result = $this->deletePrepare(
            $publication->getDocument(),
            $publication->getVideo(),
            $publication->getImages(),
            $filename
        );

        //Check if the file has been deleted
        if (!$result[3]) {
            $response=array('error'=>'File not found');
            return new JsonResponse($response,404);
        }

        $publication->setVideo($result[1]);
        $publication->setDocument($result[0]);
        $publication->setImages($result[2]);

        $em = $this->getDoctrine()->getManager();
        $em->persist($publication);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }

    private function uploadPrepare($document, $video, $images, $files): array
    {
        foreach($files as $file) {
            $filename = $this->uploader->upload($file);
            $array = explode(".", $filename);
            $extension = $array[count($array) - 1];
            if ($extension == "pdf") {
                $document[count($document)] = $filename;
                $filesize = filesize($file);
                $filesize = round($filesize / 1024);
                $document[count($document)] = $filesize;
            }
            elseif ($extension == "mp4") {
                $video[count($video)] = $filename;
                $filesize = filesize($file);
                $filesize = round($filesize / 1024);
                $video[count($video)] = $filesize;
            }
            elseif ($extension == "jpg" or $extension == "jpeg" or $extension == "png") {
                $images[count($images)] = $filename;
                $filesize = filesize($file);
                $filesize = round($filesize / 1024);
                $images[count($images)] = $filesize;
            }
        }

        return [$document, $video, $images];
    }

    private function deletePrepare($document, $video, $images, $filename): array
    {
        if (in_array($filename, $document)) {
            $deleted = $this->uploader->deleteFile($filename);
            $index = array_search($filename, $document);
            unset($document[$index]);
            $document = array_values($document);
        }
        elseif (in_array($filename, $video)) {
            $deleted = $this->uploader->deleteFile($filename);
            $index = array_search($filename, $video);
            unset($video[$index]);
            $video = array_values($video);
        }
        elseif (in_array($filename, $images)) {
            $deleted = $this->uploader->deleteFile($filename);
            $index = array_search($filename, $images);
            unset($images[$index]);
            $images = array_values($images);
        }
        else {
            $deleted = false;
        }
        return [$document, $video, $images, $deleted];
    }
}
