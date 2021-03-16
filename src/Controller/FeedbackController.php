<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Category;
use App\Entity\Expert;
use App\Entity\Feedback;
use App\Service\UploaderService;
use App\Entity\User;
use Psr\Container\ContainerInterface;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;

class FeedbackController extends AbstractController
{
    #[Route('/api/feedback', name: 'feedback_post', methods: ['POST'])]
    /**
     * @OA\Parameter(name="publication", in="query", description="Publication id", required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(response=200, description="Adds a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="exepert", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="integer"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     */
    public function postFeedback(Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['username']]);
        $feedback = $serializer->deserialize($request->getContent(), Feedback::class, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['username']]);

        //Trabajamos los datos como queramos
        $em = $this->getDoctrine()->getManager();
        //Obtenemos la publicacion
        $publication = $this->getDoctrine()->getRepository(Publication::class)->find($request->query->get('publication'));
        dump($publication);
        $feedback->setPublication($publication);
        //Decidimos el experto
        $userdata = $this->getDoctrine()->getRepository(User::class)->findBy(['username'=>$user->getUsername()]);
        dump($userdata);
        $expert = $this->getDoctrine()->getRepository(Expert::class)->findBy(['userdata'=>$userdata[0]]);
        dump($expert);
        $feedback->setExpert($expert[0]);
        $em->persist($feedback);
        $em->flush();
        $id = $this->getDoctrine()->getRepository(Feedback::class)->findBy(['publication'=>$feedback->getPublication()], ['id'=>'DESC'])[0]->getId();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($feedback, 'json', [AbstractNormalizer::GROUPS => ['feedbacks'], AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'feedback'=>json_decode($data),
            'id'=>$id
        );

        return new JsonResponse($response,200);
    }

    #[Route('/api/feedback', name: 'feedback_get', methods: ['GET'])]
    /**
     * @OA\Response(response=200, description="Gets all feedbacks",
     *     @OA\JsonContent(type="array", @OA\Items(
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="exepert", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="integer"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     */
    public function getFeedbacks(): Response {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $feedbacks = $this->getDoctrine()->getRepository(Feedback::class)->findAll();
        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($feedbacks, 'json', [AbstractNormalizer::GROUPS => ['feedbacks']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'feedbacks'=>json_decode($data)
        );

        return new JsonResponse($response, 200);
    }

    #[Route('/api/feedback/{id}', name: 'feedback_get_id', methods: ['GET'])]
    /**
     * @OA\Response(response=200, description="Gets a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="exepert", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="integer"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     */
    public function getFeedback($id): Response {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Trabajamos los datos como queramos
        $feedbacks = $this->getDoctrine()->getRepository(Feedback::class)->find($id);
        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($feedbacks, 'json', [AbstractNormalizer::GROUPS => ['feedbacks']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'feedback'=>json_decode($data)
        );

        return new JsonResponse($response, 200);
    }
}
