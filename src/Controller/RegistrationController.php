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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class RegistrationController extends AbstractController
{

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    #[Route('/api/register/type/{type}', name: 'register', methods: ['POST'])]
    /**
     * @Route("/api/register/type/{type}", name="register", methods={"POST"})
     * @OA\Response(response=200, description="Adds an expert or apprentice user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=400, description="Bad request",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * ))
     * @OA\Tag(name="Registration")
     * @Security()
     * @param $type
     * @param Request $request
     * @return Response
     */
    public function register($type, Request $request): Response
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
        $user = $serializer->deserialize($request->getContent(),User::class, 'json');
        $password = $this->encoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Check if user already exists
        $old = $doctrine->getRepository(User::class)->findOneBy(['username'=>$user->getUSername()]);
        if ($old != null) {
            $response=array('error'=>'User already exists');
            return new JsonResponse($response,409);
        }

        //Set type and roles
        $user->setRoles(['ROLE_USER']);
        if ($type == 'apprentice') {
            $apprentice = new Apprentice();
            $apprentice->setUsername($user->getUsername());
            $apprentice->setUserdata($user);
            $em->persist($apprentice);
        }
        elseif ($type == 'expert') {
            $expert = new Expert();
            $expert->setUsername($user->getUsername());
            $expert->setUserdata($user);
            $em->persist($expert);
        }
        else {
            $response=array('error'=>'User type unknown');
            return new JsonResponse($response,400);
        }

        //Save the user
        $em->persist($user);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response=array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/register/admin', name: 'register_admin', methods: ['POST'])]
    /**
     * @Route("/api/register/admin", name="register_admin", methods={"POST"})
     * @OA\Response(response=200, description="Adds an admin user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=401, description="Unauthorized",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * ))
     * @OA\Tag(name="Registration")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function registerAdmin(Request $request): Response
    {
        //Only admins can register admins
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserialize to obtain object data
        $user = $serializer->deserialize($request->getContent(),User::class, 'json');
        $password = $this->encoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Check if user already exists
        $old = $doctrine->getRepository(User::class)->findOneBy(['username'=>$user->getUSername()]);
        if ($old != null) {
            $response=array('error'=>'User already exists');
            return new JsonResponse($response,409);
        }

        //Set type and roles
        $user->setRoles(['ROLE_ADMIN']);

        //Save the user
        $em->persist($user);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response=array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }
}
