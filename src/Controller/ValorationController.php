<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Feedback;
use App\Entity\Valoration;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

class ValorationController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/rating/feedback/{id}', name: 'rating_post', methods: ['POST'])]
    /**
     * @Route("/api/rating/feedback/{id}", name="rating_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a valoration",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="rating", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="grade", type="integer"),
     *          @OA\Property(property="date", type="string", format="date-time"))
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
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Ratings")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postRating($id, Request $request): Response
    {
        //Deserialize to obtain object data
        $user = $this->getUser();
        $rating = $this->serializer->deserialize($request->getContent(), Valoration::class, 'json');

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the feedback
        $feedback = $doctrine->getRepository(Feedback::class)->find($id);
        if ($feedback == null) {
            $response=array('error'=>'Feedback not found');
            return new JsonResponse($response,404);
        }
        if ($feedback->getValoration() != null) {
            $response=array('error'=>'Feedback already rated');
            return new JsonResponse($response,409);
        }
        $rating->setFeedback($feedback);
        $rating->setExpert($feedback->getExpert());

        //Get the apprentice
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['username' => $user->getUsername()]);
        if ($apprentice == null) {
            $response=array('error'=>'User is not apprentice');
            return new JsonResponse($response,409);
        }

        //Check if the apprentice is the correct one
        $correct_apprentice = $feedback->getPublication()->getApprentice()->getUsername();
        if ($correct_apprentice != $apprentice->getUsername()) {
            $response=array('error'=>'You did not receive the feedback');
            return new JsonResponse($response,409);
        }
        $rating->setApprentice($apprentice);

        $feedback->setValoration($rating);

        //Save
        $em->persist($rating);
        $em->persist($feedback);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($rating, 'json', [
            AbstractNormalizer::GROUPS => ['ratings']
        ]);

        //Create the response
        $response=array('rating'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/rating/{id}', name: 'rating_put', methods: ['PUT'])]
    /**
     * @Route("/api/rating/{id}", name="rating_put", methods={"PUT"})
     * @OA\Response(response=200, description="Adds a valoration",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="rating", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="grade", type="integer"))
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
     * @OA\Tag(name="Ratings")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function putRating($id, Request $request): Response
    {
        //Deserialize to obtain object data
        $new = $this->serializer->deserialize($request->getContent(), Valoration::class, 'json');

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the old one
        $old = $doctrine->getRepository(Valoration::class)->find($id);
        if ($old == null) {
            $response=array('error'=>'Rating not found');
            return new JsonResponse($response,404);
        }

        //Update the grade
        $old->setGrade($new->getGrade());
        $em->persist($old);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($old, 'json', [
            AbstractNormalizer::GROUPS => ['ratings']
        ]);

        ///Create the response
        $response=array('rating'=>json_decode($data));

        return new JsonResponse($response,200);
    }
}
