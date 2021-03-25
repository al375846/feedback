<?php

namespace App\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class RankingController extends AbstractController
{
    #[Route('/api/ranking/rated/experts', name: 'rated_experts', methods: ['GET'])]
    /**
     * @Route("/api/ranking/rated/experts", name="rated_experts", methods={"GET"})
     * @OA\Response(response=200, description="Gets all categories",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="categories", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="children", type="array",
     *     @OA\Items(type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     */
    public function getRatedExperts(CategoryTree $categoryTree): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $ordercat = $categoryTree->buildTree();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($ordercat, 'json');

        //Puede tener los atributos que se quieran
        $response=array(
            'categories'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/ranking/active/experts', name: 'active_experts', methods: ['GET'])]
    /**
     * @Route("/api/ranking/active/experts", name="active_experts", methods={"GET"})
     * @OA\Response(response=200, description="Gets all categories",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="categories", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="children", type="array",
     *     @OA\Items(type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     */
    public function getActiveExperts(CategoryTree $categoryTree): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $ordercat = $categoryTree->buildTree();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($ordercat, 'json');

        //Puede tener los atributos que se quieran
        $response=array(
            'categories'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/ranking/active/categories', name: 'active_categories', methods: ['GET'])]
    /**
     * @Route("/api/ranking/active/categories", name="active_categories", methods={"GET"})
     * @OA\Response(response=200, description="Gets all categories",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="categories", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="children", type="array",
     *     @OA\Items(type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     */
    public function getActiveCategories(CategoryTree $categoryTree): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $ordercat = $categoryTree->buildTree();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($ordercat, 'json');

        //Puede tener los atributos que se quieran
        $response=array(
            'categories'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
