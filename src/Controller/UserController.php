<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    #[Route('/api/user/{username}', name: 'user_get', methods: ['GET'])]
    /**
     * @Route("/api/user/{username}", name="user_get", methods={"GET"})
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
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
     */
    public function getUserdata($username): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        try {
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(['username' => $username])[0];
        } catch (\Throwable $e) {
            $response=array('error'=>'Usuario no existe');
            return new JsonResponse($response,404);
        }

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($user, 'json',
            [AbstractNormalizer::GROUPS => ['profile']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'user'=>json_decode($data)
        );

        return new JsonResponse($response, 200);
    }

    #[Route('/api/user/{username}', name: 'user_put', methods: ['PUT'])]
    /**
     * @Route("/api/user/{username}", name="user_put", methods={"PUT"})
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
     * @OA\Response(response=404, description="Not found",
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
     */
    public function putUserdata($username, Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $newuser= $serializer->deserialize($request->getContent(), User::class, 'json');

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Obtenemos al usuario
        try {
            $olduser = $doctrine->getRepository(User::class)->findBy(['username' => $username])[0];
        } catch (\Throwable $e) {
            $response=array('error'=>'Usuario no existe');
            return new JsonResponse($response,404);
        }

        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username' => $username]);

        $olduser->setUsername($newuser->getUsername());
        if ($apprentice != null) {
            $apprentice->setUsername($olduser->getUsername());
            $em->persist($apprentice);
        }
        if ($expert != null) {
            $expert->setUsername($olduser->getUsername());
            $em->persist($expert);
        }
        $password = $this->encoder->encodePassword($olduser, $newuser->getPassword());
        $olduser->setPassword($password);
        $olduser->setEmail($newuser->getEmail());
        $olduser->setName($newuser->getName());
        $olduser->setLastname($newuser->getLastname());
        $olduser->setAddress($newuser->getAddress());
        $olduser->setPhone($newuser->getPhone());

        $em->persist($olduser);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($olduser, 'json',
            [AbstractNormalizer::GROUPS => ['profile']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'user'=>json_decode($data)
        );

        return new JsonResponse($response, 200);
    }

    #[Route('/api/user/{username}', name: 'user_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/user/{username}", name="user_delete", methods={"DELETE"})
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
    public function deleteUser($username): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Obtenemos la sugerencia
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($user == null) {
            $response=array('error'=>'Usuario no existe');
            return new JsonResponse($response,404);
        }

        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $username]);
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username' => $username]);

        if ($apprentice != null) {
            $em->remove($apprentice);
        }
        if ($expert != null) {
            $em->remove($expert);
        }

        $em->remove($user);
        $em->flush();

        //Puede tener los atributos que se quieran
        $response=array(
            'deleted'=>true
        );

        return new JsonResponse($response,200);
    }
}
