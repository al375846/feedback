<?php

namespace App\Controller;

use App\Entity\ExpertCategories;
use App\Entity\Feedback;
use App\Entity\Incidence;
use App\Entity\Publication;
use App\Service\SerializerService;
use App\Utils\CategoryTree;
use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


class CategoryController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

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

        //Deserialize to obtain object data
        $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json');

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the parent category
        $parentName = $category->getParent();
        if($parentName !== null) {
            $parentName = $parentName->getName();
            $parent = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$parentName]);
            if ($parent === null) {
                $response=array('error'=>'Parent category not found');
                return new JsonResponse($response,404);
            }
            $category->setParent($parent);
        }

        //Save the category
        $em->persist($category);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($category, 'json', [
            AbstractNormalizer::GROUPS => ['categories']
        ]);

        //Create the response
        $response = array('category'=>json_decode($data));

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
        //Get the parent categories
        $categories = $this->getDoctrine()->getRepository(Category::class)->findParentCategories();

        //Serialize the response data
        $data = $this->serializer->serialize($categories, 'json', [
            AbstractNormalizer::GROUPS => ['children']
        ]);

        //Create the response
        $response = array('categories'=>json_decode($data));

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
        //Get categories
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        //Serialize the response data
        $data = $this->serializer->serialize($categories, 'json', [
            AbstractNormalizer::GROUPS => ['categories']
        ]);

        //Create the response
        $response = array('categories'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/category/{id}', name: 'category_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/category/{id}", name="category_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a category",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Categories")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deleteCategory($id): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the category
        $category = $doctrine->getRepository(Category::class)->find($id);
        if ($category === null) {
            $response = array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }

        $publications = $doctrine->getRepository(Publication::class)->findBy(['category' => $category]);
        foreach ($publications as $pub) {
            $incidences = $doctrine->getRepository(Incidence::class)->findBy(['publication' => $pub]);
            foreach ($incidences as $inc)
                $em->remove($inc);
            $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['publication' => $pub]);
            foreach ($feedbacks as $feed)
                $em->remove($feed);
            $em->remove($pub);
        }
        $categories = $doctrine->getRepository(Category::class)->findBy(['parent' => $category]);
        foreach ($categories as $cat) {
            $category->removeSubcategory($cat);
            $cat->setParent(null);
            $em->remove($cat);
        }

        $fav = $doctrine->getRepository(ExpertCategories::class)->findBy(['category' => $category]);
        foreach ($fav as $cat) {
            $em->remove($cat);
        }

        //Remove the category
        $em->remove($category);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }

    #[Route('/api/category/{id}', name: 'category_put', methods: ['PUT'])]
    /**
     * @Route("/api/category/{id}", name="category_put", methods={"PUT"})
     * @OA\Response(response=200, description="Edits a category",
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
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function putCategory($id, Request $request): Response
    {
        //Only admins can edit categories
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the category
        $category = $doctrine->getRepository(Category::class)->find($id);
        if ($category === null) {
            $response = array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }

        //Deserialize to obtain object data
        $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $category
        ]);

        //Get the parent category
        $parentName = $category->getParent();
        if($parentName !== null) {
            $parentName = $parentName->getName();
            $parent = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$parentName]);
            if ($parent === null) {
                $response = array('error'=>'Parent category not found');
                return new JsonResponse($response,404);
            }
            $category->setParent($parent);
        }

        //Save the category
        $em->persist($category);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($category, 'json', [
            AbstractNormalizer::GROUPS => ['categories']
        ]);

        //Create the response
        $response = array('category'=>json_decode($data));

        return new JsonResponse($response,200);
    }
}
