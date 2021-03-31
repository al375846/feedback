<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\NoActiveUser;
use App\Entity\User;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
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

    #[Route('/api/user', name: 'user_get', methods: ['GET'])]
    /**
     * @Route("/api/user", name="user_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets a user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
     */
    public function getUserdata(): Response
    {
        //Get the user
        $user = $this->getUser();

        //Serialize the response data
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response=array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/user', name: 'user_put', methods: ['PUT'])]
    /**
     * @Route("/api/user", name="user_put", methods={"PUT"})
     * @OA\Response(response=200, description="Edits a user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="user", type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * )))
     * @OA\Response(response=409, description="Username already exists",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="lastname", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="phone", type="string")
     * ))
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function putUserdata(Request $request): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get old user
        $user = $this->getUser();
        $username = $user->getUsername();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username' => $username]);

        //Deserialize to obtain object data
        $this->serializer->deserialize($request->getContent(), User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user
        ]);

        //Check if new user is taken
        $exists = $doctrine->getRepository(User::class)->findOneBy(['username' => $user->getUSername()]);
        if ($exists != null) {
            $response=array('error'=>'Username already exists');
            return new JsonResponse($response,409);
        }

        //Change user data
        if ($apprentice != null) {
            $apprentice->setUsername($user->getUsername());
            $em->persist($apprentice);
        }
        if ($expert != null) {
            $expert->setUsername($user->getUsername());
            $em->persist($expert);
        }
        $password = $this->encoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);

        //Save new user
        $em->persist($user);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

        //Create the response
        $response=array('user'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/user', name: 'user_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/user", name="user_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a user",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
     */
    public function deleteUser(): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the user
        $user = $this->getUser();
        $username = $user->getUsername();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username' => $username]);

        //Prepare for set to non active
        $nonactive = new NoActiveUser();
        $nonactive->setUsername($user->getUsername());
        $nonactive->setPassword($user->getPassword());
        $nonactive->setEmail($user->getEmail());
        $nonactive->setName($user->getName());
        $nonactive->setLastname($user->getLastname());
        $nonactive->setAddress($user->getAddress());
        $nonactive->setPhone($user->getPhone());
        $nonactive->setRoles($user->getRoles());

        //Remove the user
        if ($apprentice != null) {
            $nonactive->seType('apprentice');
            $apprentice->setUserdata(null);
        }
        if ($expert != null) {
            $nonactive->seType('expert');
            $expert->setUserdata(null);
        }
        $em->persist($nonactive);
        $em->remove($user);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }
}
