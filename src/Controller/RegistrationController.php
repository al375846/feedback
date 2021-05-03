<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\NoActiveUser;
use App\Entity\User;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class RegistrationController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder, SerializerService $serializerService)
    {
        $this->encoder = $encoder;
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/register/{type}', name: 'register', methods: ['POST'])]
    /**
     * @Route("/api/register/{type}", name="register", methods={"POST"})
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
        //Deserialize to obtain object data
        $user = $this->serializer->deserialize($request->getContent(),User::class, 'json');
        $password = $this->encoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Check if user already exists
        $old = $doctrine->getRepository(User::class)->findOneBy(['username'=>$user->getUSername()]);
        if ($old !== null) {
            $response = array('error'=>'User already exists');
            return new JsonResponse($response,409);
        }

        //Set type and roles
        $user->setRoles(['ROLE_USER']);
        if ($type === 'apprentice') {
            $apprentice = new Apprentice();
            $apprentice->setUsername($user->getUsername());
            $apprentice->setUserdata($user);
            $em->persist($apprentice);
        }
        elseif ($type === 'expert') {
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
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response = array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/recovery', name: 'recovery', methods: ['POST'])]
    /**
     * @Route("/api/recovery", name="recovery", methods={"POST"})
     * @OA\Response(response=200, description="Recovers an expert or apprentice user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Response(response=404, description="Not found",
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
     *     @OA\Property(property="password", type="string")
     * ))
     * @OA\Tag(name="Registration")
     * @Security()
     * @param Request $request
     * @return Response
     */
    public function recovery(Request $request): Response
    {
        //Deserialize to obtain object data
        $user = $this->serializer->deserialize($request->getContent(),User::class, 'json');
        $password = $user->getPassword();
        $username = $user->getUsername();

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get user
        $old = $doctrine->getRepository(NoActiveUser::class)->findOneBy(['username'=>$username]);

        //Set userdata
        $user->setEmail($old->getEmail());
        $user->setName($old->getName());
        $user->setLastname($old->getLastname());
        $user->setAddress($old->getAddress());
        $user->setPhone($old->getPhone());
        $user->setRoles($old->getRoles());
        $user->setPassword($old->getPassword());

        //Check if is the right user
        $match = $this->encoder->isPasswordValid($user, $password);
        if ($match === false) {
            $response = array('error'=>'Password is not correct');
            return new JsonResponse($response,404);
        }

        if ($old->getType() === 'apprentice') {
            $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username'=>$username]);
            $apprentice->setUserdata($user);
            $em->persist($apprentice);
        }
        if ($old->getType() === 'expert') {
            $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username'=>$username]);
            $expert->setUserdata($user);
            $em->persist($expert);
        }

        //Save the user
        $em->persist($user);
        $em->remove($old);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response = array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/check_username', name: 'check_username', methods: ['POST'])]
    /**
     * @Route("/api/check_username", name="check_username", methods={"POST"})
     * @OA\Response(response=200, description="Checks if username exists",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="exists", type="boolean"),
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
     *     @OA\Property(property="username", type="string")
     * ))
     * @OA\Tag(name="Registration")
     * @Security()
     * @param Request $request
     * @return Response
     */
    public function checkUsername(Request $request): Response
    {
        //Deserialize to obtain object data
        $user = $this->serializer->deserialize($request->getContent(),User::class, 'json');

        //Get the doctrine
        $doctrine = $this->getDoctrine();

        //Get user
        $name = $doctrine->getRepository(User::class)->findOneBy(['username'=>$user->getUsername()]);
        if ($name === null)
            $ret = false;
        else
            $ret = true;

        //Create the response
        $response = array('exists'=>$ret);

        return new JsonResponse($response, 200);
    }

    #[Route('/api/recovery/password', name: 'recovery_password', methods: ['POST'])]
    /**
     * @Route("/api/recovery/password", name="recovery_password", methods={"POST"})
     * @OA\Response(response=200, description="Recovers the password of a user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Response(response=404, description="Not found",
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
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="password", type="string")
     * ))
     * @OA\Tag(name="Registration")
     * @Security()
     * @param Request $request
     * @return Response
     */
    public function recoveryPassword(Request $request): Response
    {
        //Deserialize to obtain object data
        $user = $this->serializer->deserialize($request->getContent(),User::class, 'json');
        $email = $user->getEmail();
        $username = $user->getUsername();

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get user
        $old = $doctrine->getRepository(User::class)
            ->findOneBy(['username'=>$username, 'email'=>$email]);
        $password = $this->encoder->encodePassword($old, $user->getPassword());
        $old->setPassword($password);

        //Save the user
        $em->persist($user);
        $em->remove($old);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response = array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }
}
