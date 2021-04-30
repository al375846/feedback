<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class NotificationController extends AbstractController
{

    /**
     * @var NotificationService
     */
    private NotificationService $notification;
    public function __construct(NotificationService $notificationService)
    {
        $this->notification = $notificationService;
    }

    #[Route('/api/notify', name: 'notify', methods: ['POST'])]
    /**
     * @Route("/api/notify", name="notify", methods={"POST"})
     * @OA\Response(response=200, description="Adds a valoration",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="send", type="boolean")
     * ))
     * @OA\Tag(name="Notifications")
     * @return Response
     */
    public function notify(): Response
    {
        $this->notification->sendMessage();

        //Create the response
        $response=array('send'=>true);

        return new JsonResponse($response,200);
    }
}
