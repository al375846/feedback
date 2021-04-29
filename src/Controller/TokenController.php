<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\Onesignal;
use App\Entity\User;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;

class TokenController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/login_check', name: 'login_check', methods: ['POST'])]
    /**
     * @Route("/api/login_check", name="login_check", methods={"POST"})
     * @OA\Response(response=200, description="Adds a category",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="token", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="password", type="string")
     * ))
     * @OA\Tag(name="Session")
     * @Security()
     */
    public function index(): Response
    {

    }

    #[Route('/api/usertype', name: 'user_type', methods: ['GET'])]
    /**
     * @Route("/api/usertype", name="user_type", methods={"GET"})
     * @OA\Response(response=200, description="Adds a category",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="usertype", type="string")
     * ))
     * @OA\Tag(name="Session")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function getUsertype(Request $request): Response
    {
        $user = $this->getUser();
        $username = $user->getUsername();
        $doctrine = $this->getDoctrine();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username'=>$username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username'=>$username]);

        if ($apprentice != null)
            return new JsonResponse(['usertype' => 'apprentice']);
        elseif ($expert != null)
            return new JsonResponse(['usertype' => 'expert']);
        else
            return new JsonResponse(['usertype' => 'admin']);
    }

    #[Route('/api/notification', name: 'notification', methods: ['POST'])]
    /**
     * @Route("/api/notification", name="notification", methods={"POST"})
     * @OA\Response(response=200, description="Allows notification",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="done", type="boolean")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="onesignal", type="string")
     * ))
     * @OA\Tag(name="Session")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function notification(Request $request): Response
    {
        //Get the user
        $user = $this->getUser();
        $username = $user->getUsername();
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $complete = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);

        //Get request data
        $onesignal = $this->serializer->deserialize($request->getContent(),Onesignal::class, 'json');

        //Update ids
        $notificationsIds = $complete->getNotificationsids();
        if ($notificationsIds === null)
            $notificationsIds = [];
        $notificationsIds[count($notificationsIds)] = $onesignal->getOnesignal();

        $complete->setNotificationsids($notificationsIds);
        $em->persist($complete);
        $em->flush();

        //Create the response
        $response=array('done'=>true);

        return new JsonResponse($response,200);

    }

    #[Route('/api/logout', name: 'logout', methods: ['POST'])]
    /**
     * @Route("/api/logout", name="logout", methods={"POST"})
     * @OA\Response(response=200, description="Allows notification",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="done", type="boolean")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="onesignal", type="string")
     * ))
     * @OA\Tag(name="Session")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request): Response
    {
        //Get the user
        $user = $this->getUser();
        $username = $user->getUsername();
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $complete = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);

        //Get request data
        $onesignal = $this->serializer->deserialize($request->getContent(),Onesignal::class, 'json');

        //Update ids
        $notificationsIds = $complete->getNotificationsids();
        $id = $onesignal->getOnesignal();
        if (in_array($id, $notificationsIds)) {
            $index = array_search($id, $notificationsIds);
            unset($notificationsIds[$index]);
            $notificationsIds = array_values($notificationsIds);
        }
        else {
            $response=array('done'=>false);
        }

        $complete->setNotificationsids($notificationsIds);
        $em->persist($complete);
        $em->flush();

        //Create the response
        $response=array('done'=>true);

        return new JsonResponse($response,200);

    }
}
