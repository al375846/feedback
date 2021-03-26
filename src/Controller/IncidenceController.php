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
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postIncidence($id, Request $request): Response
    {
        //Initialize encoders and normalizer to serialize and deserialize
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserialize to obtain object data
        $incidence = $serializer->deserialize($request->getContent(), Incidence::class, 'json');

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the publication
        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }
        $incidence->setPublication($publication);

        //Save the incidence
        $em->persist($incidence);
        $em->flush();

        //Serialize the response data
        $data = $serializer->serialize($incidence, 'json', [
            AbstractNormalizer::GROUPS => ['incidences'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response=array('incidence'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/incidence', name: 'incidences_get', methods: ['GET'])]
    /**
     * @Route("/api/incidence", name="incidences_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all incidences",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="incidence", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string")
     * ))))
     * @OA\Tag(name="Incidences")
     * @Security(name="Bearer")
     */
    public function getIncidences(): Response
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
        $incidences = $this->getDoctrine()->getRepository(Incidence::class)->findAll();

        //Serialize the response data
        $data = $serializer->serialize($incidences, 'json', [
            AbstractNormalizer::GROUPS => ['incidences'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response=array('incidences'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/incidence/{id}', name: 'incidence_get', methods: ['GET'])]
    /**
     * @Route("/api/incidence/{id}", name="incidence_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets a incidence",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="incidence", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="publication", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="userdata", type="object",
     *              @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string")
     * )))
     * @OA\Tag(name="Incidences")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getIncidence($id): Response
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
        $incidences = $this->getDoctrine()->getRepository(Incidence::class)->find($id);

        //Serialize the response data
        $data = $serializer->serialize($incidences, 'json', [
            AbstractNormalizer::GROUPS => ['incidences']
        ]);

        //Create the response
        $response=array('incidence'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/incidence/{id}', name: 'incidence_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/incidence/{id}", name="incidence_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a incidence",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Incidences")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deleteIncidence($id): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the incidence
        $incidence = $this->getDoctrine()->getRepository(Incidence::class)->find($id);
        if ($incidence == null) {
            $response=array('error'=>'Incidence not found');
            return new JsonResponse($response,404);
        }

        //Remove the incidence
        $em->remove($incidence);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }
}
