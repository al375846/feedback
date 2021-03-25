<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\Feedback;
use App\Entity\Publication;
use App\Entity\User;
use App\Entity\Valoration;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ValorationController extends AbstractController
{
    #[Route('/api/valoration/feedback/{id}', name: 'valoration_post', methods: ['POST'])]
    /**
     * @Route("/api/valoration/feedback/{id}", name="valoration_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a valoration",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="token", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="grade", type="integer"),
     *     @OA\Property(property="username", type="string")
     * ))
     * @OA\Tag(name="Valorations")
     * @Security(name="Bearer")
     */
    public function postValoration($id, Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $user = $serializer->deserialize($request->getContent(),
            User::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['username']]);
        $valoration = $serializer->deserialize($request->getContent(),
            Valoration::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['grade']]);

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Obtenemos el feedback
        $feedback = $doctrine->getRepository(Feedback::class)->find($id);
        if ($feedback == null) {
            $response=array('error'=>'Feedback no existe');
            return new JsonResponse($response,404);
        }
        if ($feedback->getValoration() != null) {
            $response=array('error'=>'Feedback ya valorado');
            return new JsonResponse($response,409);
        }
        $valoration->setFeedback($feedback);
        $valoration->setExpert($feedback->getExpert());

        //Obtenemos el aprendiz
        try {
            $apprentice = $doctrine->getRepository(Apprentice::class)->findBy(['username' => $user->getUsername()])[0];
        } catch (\Throwable $e) {
            $response=array('error'=>'Usuario no existe');
            return new JsonResponse($response,404);
        }

        //Comprobamos que el usuario que valora es el que ha recibido feedback
        $correct_apprentice = $feedback->getPublication()->getApprentice()->getUsername();
        if ($correct_apprentice != $apprentice->getUsername()) {
            $response=array('error'=>'No puede valorar ese feedback');
            return new JsonResponse($response,409);
        }
        $valoration->setApprentice($apprentice);

        $feedback->setValoration($valoration);

        //Guardamos
        $em->persist($valoration);
        $em->persist($feedback);
        $em->flush();
        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($valoration, 'json',
            [AbstractNormalizer::GROUPS => ['valorations']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'valoration'=>json_decode($data),
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/valoration/{id}', name: 'valoration_put', methods: ['PUT'])]
    /**
     * @Route("/api/valoration/{id}", name="valoration_put", methods={"PUT"})
     * @OA\Response(response=200, description="Modifies a valoration",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="token", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="grade", type="integer")
     * ))
     * @OA\Tag(name="Valorations")
     * @Security(name="Bearer")
     */
    public function putValoration($id, Request $request): Response {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $newValoration = $serializer->deserialize($request->getContent(),
            Valoration::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['grade']]);

        //Trabajamos los datos como queramos
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Obtenemos la anterior
        $oldvaloration = $doctrine->getRepository(Valoration::class)->find($id);
        if ($oldvaloration == null) {
            $response=array('error'=>'Valoracion no existe');
            return new JsonResponse($response,404);
        }
        //Actualizamos valor
        $oldvaloration->setGrade($newValoration->getGrade());
        $em->persist($oldvaloration);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($oldvaloration, 'json',
            [AbstractNormalizer::GROUPS => ['valorations']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'valoration'=>json_decode($data),
        );

        return new JsonResponse($response,200);
    }
}
