<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class TokenController extends AbstractController
{
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
     * @OA\Tag(name="Login")
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
     * @OA\Tag(name="Login")
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
}
