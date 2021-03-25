<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
     */
    public function getUSertype(Request $request): Response
    {
        $token = explode(" ", $request->headers->get('Authorization'))[1];
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        $username = $jwtPayload->username;
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
