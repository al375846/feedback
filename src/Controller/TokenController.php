<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
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
     * @OA\Tag(name="Login Check")
     * @Security()
     */
    public function index(): Response
    {

    }
}
