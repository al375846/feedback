<?php

namespace App\Controller;

use App\Service\UploaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class PublicController extends AbstractController
{
    /**
     * @var UploaderService
     */
    private UploaderService $uploader;

    public function __construct(UploaderService $uploaderService)
    {
        $this->uploader = $uploaderService;
    }

    #[Route('api/public/file/{filename}', name: 'get_file_public', methods: ['GET'])]
    /**
     * @Route("api/public/file/{filename}", name="get_file_public", methods={"GET"})
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
}
