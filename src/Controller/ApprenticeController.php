<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Feedback;
use App\Entity\Publication;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ApprenticeController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/apprentice/publication', name: 'apprentice_get_publication', methods: ['GET'])]
    /**
     * @Route("/api/apprentice/publication", name="apprentice_get_publication", methods={"GET"})
     * @OA\Response(response=200, description="Gets alls publications of an apprentice",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publications", type="array", @OA\Items(type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Response(response=401, description="Unauthorized",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Apprentices")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getPublicationsUser(): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();

        //Get the apprentice
        $username = $this->getUser()->getUsername();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username'=>$username]);

        //Check if the user is an apprentice
        if ($apprentice == null) {
            $response=array('error'=>'The user is not an apprentice');
            return new JsonResponse($response, 409);
        }

        //Get publications
        $publications = $doctrine->getRepository(Publication::class)->findBy(['apprentice'=>$apprentice]);

        //Serialize the response data
        $data = $this->serializer->serialize($publications, 'json', [
            AbstractNormalizer::GROUPS => ['publications'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['apprentice']
        ]);

        //Create the response
        $response=array('publications'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/apprentice/history', name: 'apprentice_get_history', methods: ['GET'])]
    /**
     * @Route("/api/apprentice/history", name="apprentice_get_history", methods={"GET"})
     * @OA\Response(response=200, description="Gets history of an apprentice",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="history", type="array", @OA\Items(type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="content", type="string"),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Response(response=401, description="Unauthorized",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Apprentices")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getHistory(): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();

        //Get the apprentice
        $username = $this->getUser()->getUsername();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username'=>$username]);

        //Check if the user is an apprentice
        if ($apprentice == null) {
            $response=array('error'=>'The user is not an apprentice');
            return new JsonResponse($response, 409);
        }

        //Get publications
        $publications = $doctrine->getRepository(Publication::class)->findBy(['apprentice'=>$apprentice]);

        //Get feedbacks
        $feedbacks = [];
        foreach ($publications as $publication) {
            $feeds = $doctrine->getRepository(Feedback::class)->findBy(['publication'=>$publication]);
            $feedbacks = array_merge($feedbacks, $feeds);
        }

        //Create history
        $history = [];
        foreach ($publications as $publication) {
            $pub['id'] = $publication->getId();
            $pub['type'] = 'Publicacion';
            $pub['content'] = 'Has realizado la publicación *'. $publication->getTitle() . '*';
            $pub['date'] = $publication->getDate();
            $history[] = $pub;
        }

        foreach ($feedbacks as $feedback) {
            $feed['id'] = $feedback->getId();
            $feed['type'] = 'Feedback';
            $feed['content'] = 'Has recibido un feedback de *' . $feedback->getExpert()->getUsername() . '*';
            $feed['date'] = $feedback->getDate();
            $history[] = $feed;
        }

        usort($history, function($a, $b){
            return $b['date'] > $a['date'];
        });

        //Serialize the response data
        $data = $this->serializer->serialize($history, 'json');

        //Create the response
        $response=array('history'=>json_decode($data));

        return new JsonResponse($response, 200);
    }
}
