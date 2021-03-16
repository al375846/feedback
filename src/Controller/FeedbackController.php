<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FeedbackController extends AbstractController
{
    #[Route('/api/feedback', name: 'feedback_post', methods: ['POST'])]
    /**
     * @OA\Response(response=200, description="Adds a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object", @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *     @OA\Property(property="video", type="string", format="binary"),
     *     @OA\Property(property="document", type="string", format="binary"),
     *     @OA\Property(property="image", type="string", format="binary"),
     *     @OA\Property(property="publication", type="object",
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="category", type="object", @OA\Property(property="name", type="string")),
     *          @OA\Property(property="description", type="string"),
     *          @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *          @OA\Property(property="username", type="string"),
     *          @OA\Property(property="date", type="string", format="date-time"))
     *     )
     *     )
     * )
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     */
    public function postFeedback(): Response
    {
        return $this->render('feedback/index.html.twig', [
            'controller_name' => 'FeedbackController',
        ]);
    }
}
