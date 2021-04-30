<?php

namespace App\Controller;

use App\Entity\Suggestion;
use App\Entity\Category;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class SuggestionController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/suggestion', name: 'suggestion_post', methods: ['POST'])]
    /**
     * @Route("/api/suggestion", name="suggestion_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a suggestion",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="suggestion", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
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
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object", nullable="true",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Suggestions")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function postSuggestion(Request $request): Response
    {
        //Deserialize to obtain object data
        $suggestion= $this->serializer->deserialize($request->getContent(), Suggestion::class, 'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['username']]);

        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Check it doesnt exist
        $sug = $doctrine->getRepository(Suggestion::class)->findOneBy(['name'=>$suggestion->getName()]);
        if ($sug != null) {
            $response=array('error'=>'Suggestion already exists');
            return new JsonResponse($response,409);
        }

        //Get the parent category
        $parentName = $suggestion->getParent();
        if($parentName != null) {
            $parentName = $parentName->getName();
            $parent = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$parentName]);
            if ($parent == null) {
                $response=array('error'=>'Parent category not found');
                return new JsonResponse($response,404);
            }
            $suggestion->setParent($parent);
        }

        //Save the response
        $em->persist($suggestion);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($suggestion, 'json', [
            AbstractNormalizer::GROUPS => ['suggestions']
        ]);

        //Create the response
        $response=array('category'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/suggestion', name: 'suggestions_get', methods: ['GET'])]
    /**
     * @Route("/api/suggestion", name="suggestions_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all suggestions",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="suggestions", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Tag(name="Suggestions")
     * @Security(name="Bearer")
     */
    public function getSuggestions(): Response
    {
        //Get suggestions
        $suggestions = $this->getDoctrine()->getRepository(Suggestion::class)->findBy([], ['id'=>'DESC']);

        //Serialize the response data
        $data = $this->serializer->serialize($suggestions, 'json', [
            AbstractNormalizer::GROUPS => ['suggestions']
        ]);

        //Create the response
        $response=array('suggestions'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/suggestion/{id}', name: 'suggestion_get', methods: ['GET'])]
    /**
     * @Route("/api/suggestion/{id}", name="suggestion_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets a suggestion",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="suggestion", type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="parent", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Tag(name="Suggestions")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getSuggestion($id): Response
    {
        //Get the suggestion
        $suggestion = $this->getDoctrine()->getRepository(Suggestion::class)->find($id);

        //Serialize the response data
        $data = $this->serializer->serialize($suggestion, 'json',
            [AbstractNormalizer::GROUPS => ['suggestions']]);

        //Create the response
        $response=array('suggestion'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/suggestion/{id}', name: 'suggestion_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/suggestion/{id}", name="suggestion_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a fav category of an exepert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Suggestions")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deleteSuggestion($id): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the suggestion
        $suggestion = $this->getDoctrine()->getRepository(Suggestion::class)->find($id);
        if ($suggestion == null) {
            $response=array('error'=>'Suggestion not found');
            return new JsonResponse($response,404);
        }

        //Remove the suggestion
        $em->remove($suggestion);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }
}
