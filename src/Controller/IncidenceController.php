<?php

namespace App\Controller;

use App\Entity\Incidence;
use App\Entity\Publication;
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

class IncidenceController extends AbstractController
{
    #[Route('/api/incidence/publication/{id}', name: 'incidence_post', methods: ['POST'])]
    /**
     * @Route("/api/incidence/publication/{id}", name="incidence_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a incidence",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="incidence", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string")
     * ))
     * @OA\Tag(name="Incidences")
     * @Security(name="Bearer")
     */
    public function postIncidence($id, Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $incidence = $serializer->deserialize($request->getContent(),
            Incidence::class, 'json');

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publicacion no encontrada');
            return new JsonResponse($response,404);
        }

        $incidence->setPublication($publication);

        $em->persist($incidence);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($incidence, 'json',
            [AbstractNormalizer::GROUPS => ['incidences'], AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'incidence'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
