<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Publication;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ApprenticeController extends AbstractController
{
    #[Route('/api/apprentice/{username}/publication', name: 'apprentice_get_publication', methods: ['GET'])]
    /**
     * @OA\Response(response=200, description="Gets a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object", @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Apprentices")
     * @Security(name="Bearer")
     */
    public function getPublicationsUser($username): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        //Obtenemos al aprendiz
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(['username'=>$username]);
        $apprentice = $this->getDoctrine()->getRepository(Apprentice::class)->findBy(['userdata'=>$user]);
        //Obtenemos las publicaciones
        $publications = $this->getDoctrine()->getRepository(Publication::class)->findBy(['apprentice'=>$apprentice]);

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($publications, 'json', [AbstractNormalizer::GROUPS => ['publications'], AbstractNormalizer::IGNORED_ATTRIBUTES => ['apprentice']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'publications'=>json_decode($data)
        );

        return new JsonResponse($response, 200);
    }
}
