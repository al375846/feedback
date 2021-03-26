<?php

namespace App\Controller;

use App\Utils\CategoryTree;
use App\Entity\Category;
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


class CategoryController extends AbstractController
{
    #[Route('/api/category', name: 'category_post', methods: ['POST'])]
    /**
     * @Route("/api/category", name="category_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a category",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="category", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"))
     * )))
     * @OA\Response(response=401, description="Unauthorized",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=403, description="Forbbiden",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="name", type="string"))
     * ))
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function postCategory(Request $request): Response
    {
        //Only admins can post categories
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
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['username']
        ]);

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the parent category
        $parentName = $category->getParent();
        if($parentName != null) {
            $parentName = $parentName->getName();
            $parent = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$parentName]);
            if ($parent == null) {
                $response=array('error'=>'Parent category does not exist');
                return new JsonResponse($response,404);
            }
            $category->setParent($parent);
        }

        $em->persist($category);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($category, 'json', [
            AbstractNormalizer::GROUPS => ['categories']
        ]);

        //Create the response
        $response=array('category'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/category', name: 'categories_get', methods: ['GET'])]
    /**
     * @Route("/api/category", name="categories_get", methods={"GET"})
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
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     * @param CategoryTree $categoryTree
     * @return Response
     */
    public function getCategories(CategoryTree $categoryTree): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get the categories
        $categories = $categoryTree->buildTree();

        //Serialize the response data
        $data = $serializer->serialize($categories, 'json');

        //Create the response
        $response=array('categories'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/category/raw', name: 'categories_get_raw', methods: ['GET'])]
    /**
     * @Route("/api/category/raw", name="categories_get_raw", methods={"GET"})
     * @OA\Response(response=200, description="Gets all categories raw",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="categories", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"))
     * ))))
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     */
    public function getCategoriesRaw(): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Get categories
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        //Serialize the response data
        $data = $serializer->serialize($categories, 'json', [
            AbstractNormalizer::GROUPS => ['categories']
        ]);

        //Create the response
        $response=array('categories'=>json_decode($data));

        return new JsonResponse($response,200);
    }
}
