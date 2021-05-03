<?php

namespace App\Controller;

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

class AdminController extends AbstractController
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

    #[Route('/api/admin/register', name: 'register_admin', methods: ['POST'])]
    /**
     * @Route("/api/admin/register", name="register_admin", methods={"POST"})
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
     * @OA\Tag(name="Admins")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function registerAdmin(Request $request): Response
    {
        //Only admins can register admins
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
            $response=array('error'=>'User already exists');
            return new JsonResponse($response,409);
        }

        //Set type and roles
        $user->setRoles(['ROLE_ADMIN']);

        //Save the user
        $em->persist($user);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response = array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }
}
