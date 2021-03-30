<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Expert;
use App\Service\SerializerService;
use Doctrine\Common\Annotations\AnnotationReader;
use JetBrains\PhpStorm\Pure;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Routing\Annotation\Route;
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

class RankingController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/ranking/rated/experts', name: 'rated_experts', methods: ['GET'])]
    /**
     * @Route("/api/ranking/rated/experts", name="rated_experts", methods={"GET"})
     * @OA\Response(response=200, description="Gets experts order by valorations",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="ratedexperts", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="rate", type="string"),
     *     @OA\Property(property="username", type="string")
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getRatedExperts(): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        /*$encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);*/

        //Get experts
        $experts = $this->getDoctrine()->getRepository(Expert::class)->findRatedExperts();
        $rated = [];
        foreach ($experts as $expert) {
            $rate = round($expert['rate'], 2);
            $expert['rate'] = $rate;
            $rated[] = $expert;
        }

        //Serialize the response data
        $data = $this->serializer->serialize($rated, 'json');

        //Create the response
        $response=array('ratedexperts'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/ranking/active/experts', name: 'active_experts', methods: ['GET'])]
    /**
     * @Route("/api/ranking/active/experts", name="active_experts", methods={"GET"})
     * @OA\Response(response=200, description="Gets most active experts",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="activeexperts", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="username", type="string")
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getActiveExperts(): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        /*$encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);*/

        //Get data
        //$experts = $activeExperts->buildActiveExperts();
        $experts = $this->getDoctrine()->getRepository(Expert::class)->findActiveExperts();

        //Serialize the response data
        $data = $this->serializer->serialize($experts, 'json');

        //Create the response
        $response=array('activeexperts'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/ranking/active/categories', name: 'active_categories', methods: ['GET'])]
    /**
     * @Route("/api/ranking/active/categories", name="active_categories", methods={"GET"})
     * @OA\Response(response=200, description="Gets most active categories",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="activecategories", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string")
     * ))))
     * @OA\Tag(name="Rankings")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getActiveCategories(): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        /*$encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);*/

        //Get data
        $categories = $this->getDoctrine()->getRepository(Category::class)->findActiveCategories();

        //Serialize the response data
        $data = $this->serializer->serialize($categories, 'json');

        //Create the response
        $response=array('activecategories'=>json_decode($data));

        return new JsonResponse($response,200);
    }
}
