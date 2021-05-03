<?php

namespace App\Controller;

use App\Entity\Expert;
use App\Entity\Feedback;
use App\Service\NotificationService;
use App\Service\SerializerService;
use App\Entity\Publication;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class FeedbackController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;
    /**
     * @var NotificationService
     */
    private NotificationService $notification;

    public function __construct(SerializerService $serializerService, NotificationService $notificationService)
    {
        $this->serializer = $serializerService->getSerializer();
        $this->notification = $notificationService;
    }

    #[Route('/api/feedback/publication/{id}', name: 'feedback_post', methods: ['POST'])]
    /**
     * @Route("/api/feedback/publication/{id}", name="feedback_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="id", type="string"),
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postFeedback($id, Request $request): Response
    {
        //Deserialize to obtain object data
        $feedback = $this->serializer->deserialize($request->getContent(), Feedback::class, 'json');

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get publication
        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication === null) {
            $response = array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }
        $feedback->setPublication($publication);

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);
        $feedback->setExpert($expert);

        //Save the feedback
        $em->persist($feedback);
        $em->flush();

        //Notify apprentice
        $username = $publication->getApprentice()->getUsername();
        $message = 'Has recibido feedback en la publicación ' . $publication->getTitle();
        $this->notification->enqueueMessage($username, $message);

        //Serialize the response data
        $data = $this->serializer->serialize($feedback, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks']
        ]);

        //Create the response
        $response = array('feedback'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/feedback', name: 'feedback_get', methods: ['GET'])]
    /**
     * @Route("/api/feedback", name="feedback_get", methods={"GET"})
     * @OA\Parameter(name="cursor", in="query", required=false)
     * @OA\Response(response=200, description="Gets all feedbacks",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedbacks", type="array", @OA\Items(
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function getFeedbacks(Request $request): Response
    {
        //Get cursor
        $cursor = $request->query->get('cursor', -1);

        //Get feedbacks
        $itemSize = 25;
        if ($cursor == -1) {
            $dql = "SELECT f FROM App\Entity\Feedback f ORDER BY f.id DESC";
            $query = $this->getDoctrine()->getManager()->createQuery($dql)
            ->setFirstResult(0)
            ->setMaxResults($itemSize);
        }
        else {
            $dql = "SELECT f FROM App\Entity\Feedback f WHERE f.id < :cursor ORDER BY f.id DESC";
            $query = $this->getDoctrine()->getManager()->createQuery($dql)
            ->setParameter('cursor', $cursor)
            ->setFirstResult(0)
            ->setMaxResults($itemSize);
        }

        $paginator = new Paginator($query, $fetchJoinCollection = false);

        $feedbacks = [];
        foreach ($paginator as $feedback) {
            $feedbacks[] = $feedback;
        }

        //Serialize the response data
        $data = $this->serializer->serialize($feedbacks, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response = array('feedbacks'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/feedback/{id}', name: 'feedback_get_id', methods: ['GET'])]
    /**
     * @Route("/api/feedback/{id}", name="feedback_get_id", methods={"GET"})
     * @OA\Response(response=200, description="Gets a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getFeedback($id): Response {
        //Get feedback
        $feedback = $this->getDoctrine()->getRepository(Feedback::class)->find($id);
        if ($feedback === null) {
            $response = array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Serialize the response data
        $data = $this->serializer->serialize($feedback, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks']
        ]);

        //Create the response
        $response = array('feedback'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/feedback/{id}', name: 'feedback_put', methods: ['PUT'])]
    /**
     * @Route("/api/feedback/{id}", name="feedback_put", methods={"PUT"})
     * @OA\Response(response=200, description="Adds a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedback", type="object",
     *     @OA\Property(property="id", type="string"),
     *     @OA\Property(property="expert", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="grade", type="integer")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function putFeedback($id, Request $request): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get old publication
        $old = $doctrine->getRepository(Feedback::class)->find($id);
        if ($old === null) {
            $response = array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Deserialize to obtain object data
        $this->serializer->deserialize($request->getContent(), Feedback::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $old
        ]);

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);
        $old->setExpert($expert);

        //Save publication
        $em->persist($old);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($old, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks']
        ]);

        //Create the response
        $response = array('feedback'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/feedback/{id}', name: 'feedback_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/feedback/{id}", name="feedback_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Feedbacks")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deleteFeedback($id): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the feedback
        $feedback = $this->getDoctrine()->getRepository(Feedback::class)->find($id);
        if ($feedback === null) {
            $response = array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }

        //Get the expert
        $expert = $feedback->getExpert();
        if ($expert != null) {
            $expert->removeFeedback($feedback);
            $em->persist($expert);
        }
        //Remove the feedback
        $em->remove($feedback);
        $em->flush();

        //Create the response
        $response = array('deleted'=>true);

        return new JsonResponse($response,200);
    }
}
