<?php

namespace App\Controller;

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
use App\Entity\Publication;

class PublicationController extends AbstractController
{
    #[Route('/api/publication', name: 'publication_post', methods: ['POST'])]
    /**
     * @OA\Response(response=200, description="Adds a publication", @OA\JsonContent(type="object", ref=@Model(type=Publication::class, groups={"publications"})))
     * @OA\RequestBody(description="Input data format", @OA\JsonContent(type="object", ref=@Model(type=Publication::class, groups={"publications"})))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     */
    public function postPublication(Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $publication = $serializer->deserialize($request->getContent(), Publication::class, 'json');

        //Trabajamos los datos como queramos
        //as
        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($publication, 'json', [AbstractNormalizer::GROUPS => ['publications']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'publication'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
