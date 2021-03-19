<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Expert;
use App\Entity\ExpertCategories;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
class ExpertController extends AbstractController
{
    #[Route('/api/expert/{username}/category/{id}', name: 'expert_post_favcat', methods: ['POST'])]
    /**
     * @Route("/api/expert/{username}/category/{id}", name="expert_post_favcat", methods={"POST"})
     * @OA\Response(response=200, description="Adds a fav category of an expert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="exepert", type="object",
     *          @OA\Property(property="userdata", type="object",
     *              @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="favCategories", type="array", @OA\Items(type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     */
    public function postFavCategory($username, $id, Request $request): Response
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
        //Obtenemos la categoria
        $cat = $doctrine->getRepository(Category::class)->find($id);
        //Obetenemos el experto
        $userdata = $doctrine->getRepository(User::class)->findBy(['username'=>$username])[0];
        $expert = $doctrine->getRepository(Expert::class)->findBy(['userdata'=>$userdata])[0];
        //Añadimos la categoria
        $favCat = new ExpertCategories();
        $favCat->setCategory($cat);
        $favCat->setExpert($expert);
        $expert->addFavCategory($favCat);
        $em->persist($favCat);
        $em->persist($expert);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($expert, 'json',
            [AbstractNormalizer::GROUPS => ['fav_categories']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'favCategories'=>json_decode($data),
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/expert/{username}/category', name: 'expert_get_favcat', methods: ['GET'])]
    /**
     * @Route("/api/expert/{username}/category", name="expert_get_favcat", methods={"GET"})
     * @OA\Response(response=200, description="Gets fav categories of an exepert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="exepert", type="object",
     *          @OA\Property(property="userdata", type="object",
     *          @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="favCategories", type="array",
     *          @OA\Items(type="object", schema="category",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     */
    public function getFavCategory($username): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);


        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        //Obetenemos el experto
        $userdata = $doctrine->getRepository(User::class)->findBy(['username'=>$username]);
        $expert = $doctrine->getRepository(Expert::class)->findBy(['userdata'=>$userdata[0]])[0];
        //Obetemos las categorias
        $favCat = $expert->getFavCategories();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($favCat, 'json',
            [AbstractNormalizer::GROUPS => ['fav_categories']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'favCategories'=>json_decode($data),
        );

        return new JsonResponse($response,200);
    }
}
