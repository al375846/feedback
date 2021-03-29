<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\Feedback;
use App\Entity\NoActiveUser;
use App\Entity\Publication;
use App\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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

class UserController extends AbstractController
{

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
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
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get the user
        $user = $this->getUser();

        //Serialize the response data
        $data = $serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

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
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserialize to obtain object data
        $new = $serializer->deserialize($request->getContent(), User::class, 'json');

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Check if new user is taken
        $exists = $doctrine->getRepository(User::class)->findOneBy(['username' => $new->getUSername()]);
        if ($exists != null) {
            $response=array('error'=>'Username already exists');
            return new JsonResponse($response,409);
        }

        //Get old user
        $user = $this->getUser();
        $username = $user->getUsername();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username' => $username]);

        //Change user data
        $user->setUsername($new->getUsername());
        if ($apprentice != null) {
            $apprentice->setUsername($user->getUsername());
            $em->persist($apprentice);
        }
        if ($expert != null) {
            $expert->setUsername($user->getUsername());
            $em->persist($expert);
        }
        $password = $this->encoder->encodePassword($user, $new->getPassword());
        $user->setPassword($password);
        $user->setEmail($new->getEmail());
        $user->setName($new->getName());
        $user->setLastname($new->getLastname());
        $user->setAddress($new->getAddress());
        $user->setPhone($new->getPhone());

        //Save new user
        $em->persist($user);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($user, 'json', [AbstractNormalizer::GROUPS => ['profile']]);

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
