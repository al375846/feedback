<?php

namespace App\Controller;

use App\Entity\User;
use App\Utils\CategoryTree;
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
use App\Entity\Category;

class CategoryController extends AbstractController
{
    #[Route('/api/category', name: 'category_post', methods: ['POST'])]
    /**
     * @Route("/api/category", name="category_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a category",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="name", type="string"))
     * ))
     * @OA\Response(response=400, description="Error while adding",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="username", type="string"),
     * ))
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     */
    public function postCategory(Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['username']]);

        $user = $serializer->deserialize($request->getContent(), User::class, 'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['name', 'description', 'parent']]);

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        //Obtenemos el usuario
        $username = $user->getUsername();
        $user = $doctrine->getRepository(User::class)->findBy(['username'=>$username])[0];
        //Comprobamos que sea admin
        $roles = $user->getRoles();
        if (!in_array("ROLE_ADMIN", $roles)) {
            $response=array(
                'status'=>400,
                'publication'=>'Error: El usuario no es administrador, no puede añadir categorias'
            );
            return new JsonResponse($response,400);
        }
        //Obtenemos la categoria padre
        $parentName = $category->getParent();
        //$category->setParent(null);
        if($parentName != null) {
            $parentName = $parentName->getName();
            $parent = $doctrine->getRepository(Category::class)->findBy(['name'=>$parentName])[0];
            $category->setParent($parent);
        }
        $em->persist($category);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($category, 'json',
            [AbstractNormalizer::GROUPS => ['categories']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'category'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/category', name: 'categories_get', methods: ['GET'])]
    /**
     * @Route("/api/category", name="categories_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all categories",
     *     @OA\JsonContent(type="array",@OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="children", type="array",
     *     @OA\Items(type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * )))
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     */
    public function getCategories(CategoryTree $categoryTree): Response
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
            'status'=>200,
            'categories'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
