<?php

namespace App\Controller;

use App\Entity\Suggestion;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class SuggestionController extends AbstractController
{
    #[Route('/api/suggestion', name: 'suggestion_post', methods: ['POST'])]
    /**
     * @Route("/api/suggestion", name="suggestion_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a suggestion",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="suggestion", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"))
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="name", type="string")),
     * ))
     * @OA\Tag(name="Suggestions")
     * @Security(name="Bearer")
     */
    public function postSuggestion(Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $suggestion= $serializer->deserialize($request->getContent(), Suggestion::class, 'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['username']]);

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Comprobamos que no existe previamente

        $sugg = $doctrine->getRepository(Suggestion::class)->findBy(['name'=>$suggestion->getName()])[0];
        if ($sugg != null) {
            $response=array('error'=>'Sugerencia ya existe');
            return new JsonResponse($response,409);
        }

        //Obtenemos la categoria padre
        try {
            $parentName = $suggestion->getParent();
            if($parentName != null) {
                $parentName = $parentName->getName();
                $parent = $doctrine->getRepository(Category::class)->findBy(['name'=>$parentName])[0];
                $suggestion->setParent($parent);
            }
        } catch (\Throwable $e) {
            $response=array('error'=>'Catergoria padre no existe');
            return new JsonResponse($response,404);
        }
        $em->persist($suggestion);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($suggestion, 'json',
            [AbstractNormalizer::GROUPS => ['suggestions']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'category'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
