<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Expert;
use App\Entity\ExpertCategories;
use App\Entity\Feedback;
use App\Entity\Valoration;
use App\Service\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ExpertController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/expert/category/{id}', name: 'expert_post_favcat', methods: ['POST'])]
    /**
     * @Route("/api/expert/category/{id}", name="expert_post_favcat", methods={"POST"})
     * @OA\Response(response=200, description="Adds a fav category of an expert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="favCategory", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"))
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function postFavCategory($id, Request $request): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get category
        $cat = $doctrine->getRepository(Category::class)->find($id);
        if ($cat == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);

        //Add category
        $favCat = new ExpertCategories();
        $favCat->setCategory($cat);
        $favCat->setExpert($expert);
        $favCatExists = $doctrine->getRepository(ExpertCategories::class)->findBy([
            'category'=>$cat, 'expert' =>$expert
        ]);
        if (count($favCatExists) > 0 ) {
            $response=array('error'=>'Category already favourite');
            return new JsonResponse($response,409);
        }
        $expert->addFavCategory($favCat);
        $em->persist($favCat);
        $em->persist($expert);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($favCat, 'json', [
            AbstractNormalizer::GROUPS => ['fav_categories']
        ]);

        //Create the response
        $response=array('favCategory'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/expert/category', name: 'expert_get_favcat', methods: ['GET'])]
    /**
     * @Route("/api/expert/category", name="expert_get_favcat", methods={"GET"})
     * @OA\Response(response=200, description="Gets fav categories of an exepert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="favCategories", type="array",
     *          @OA\Items(type="object", schema="category",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string")))
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     */
    public function getFavCategory(): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);

        //Get categories
        $favCat = $expert->getFavCategories();
        $favourite = [];
        foreach ($favCat as $fav) {
            $favourite[] = $fav->getCategory();
        }

        //Serialize the response data
        $data = $this->serializer->serialize($favourite, 'json', [
            AbstractNormalizer::GROUPS => ['fav_categories']
        ]);

        //Create the response
        $response=array('favCategories'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/expert/category/{id}', name: 'expert_get_favcat_one', methods: ['GET'])]
    /**
     * @Route("/api/expert/category/{id}", name="expert_get_favcat_one", methods={"GET"})
     * @OA\Response(response=200, description="Gets a fav category of an exepert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="favCategory", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"))
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getOneFavCategory($id): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);

        //Get category
        $category = $doctrine->getRepository(Category::class)->find($id);
        if ($category == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }

        //Get fav category
        $favCat = $doctrine->getRepository(ExpertCategories::class)->findOneBy([
            'expert'=>$expert, 'category'=>$category
        ]);
        if ($favCat == null) {
            $response=array('error'=>'Favourite category not found');
            return new JsonResponse($response,404);
        }

        //Serialize the response data
        $data = $this->serializer->serialize($favCat->getCategory(), 'json', [
            AbstractNormalizer::GROUPS => ['fav_categories']
        ]);

        //Create the response
        $response=array('favCategory'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/expert/category/{id}', name: 'delete_fav_category', methods: ['DELETE'])]
    /**
     * @Route("/api/expert/category/{id}", name="delete_fav_category", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a fav category of an exepert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deleteFavCategory($id): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get expert
        $user = $this->getUser();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['userdata'=>$user]);

        //Get category
        $category = $doctrine->getRepository(Category::class)->find($id);
        if ($category == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }

        $favCat = $doctrine->getRepository(ExpertCategories::class)->findOneBy([
            'expert'=>$expert, 'category'=>$category
        ]);

        if ($favCat == null) {
            $response=array('error'=>'Favourite category not found');
            return new JsonResponse($response,404);
        }

        $expert->removeFavCategory($favCat);
        $em->persist($expert);
        $em->remove($favCat);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }

    #[Route('/api/expert/feedback', name: 'expert_get_feedback', methods: ['GET'])]
    /**
     * @Route("/api/expert/feedback", name="expert_get_feedback", methods={"GET"})
     * @OA\Response(response=200, description="Gets alls feedbacks of an expert",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedbacks", type="array", @OA\Items(type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="valoration", type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="grade", type="integer")),
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
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getPublicationsUser(): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();

        //Get the expert
        $username = $this->getUser()->getUsername();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username'=>$username]);

        //Check if the user is an apprentice
        if ($expert == null) {
            $response=array('error'=>'The user is not an expert');
            return new JsonResponse($response, 409);
        }

        //Get feedbacks
        $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['expert'=>$expert]);

        //Serialize the response data
        $data = $this->serializer->serialize($feedbacks, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['expert']
        ]);

        //Create the response
        $response=array('feedbacks'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/expert/history', name: 'expert_get_history', methods: ['GET'])]
    /**
     * @Route("/api/expert/history", name="expert_get_history", methods={"GET"})
     * @OA\Response(response=200, description="Gets history of an expert",
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
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     * @return Response
     */
    public function getHistory(): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();

        //Get the apprentice
        $username = $this->getUser()->getUsername();
        $expert = $doctrine->getRepository(Expert::class)->findOneBy(['username'=>$username]);

        //Check if the user is an apprentice
        if ($expert == null) {
            $response=array('error'=>'The user is not an expert');
            return new JsonResponse($response, 409);
        }

        //Get feedbacks
        $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['expert'=>$expert]);

        //Get ratings
        $ratings = [];
        foreach ($feedbacks as $feedback) {
            $ratings[] = $feedback->getValoration();
        }

        //Create history
        $history = [];
        foreach ($feedbacks as $feedback) {
            $feed['id'] = $feedback->getId();
            $feed['type'] = 'feedback';
            $feed['content'] = 'Has realizado un feedback: en la publicación: ' . $feedback->getPublication()->getTitle();
            $feed['date'] = $feedback->getDate();
            $history[] = $feed;
        }

        foreach ($ratings as $rating) {
            if ($rating != null) {
                $feed['id'] = $rating->getId();
                $feed['type'] = 'rating';
                $feed['content'] = 'Has recibo una valoración de '.$rating->getGrade(). ' en el feedback de la publicación: '
                    . $rating->getFeedback()->getPublication()->getTitle();
                $feed['date'] = $rating->getDate();
                $history[] = $feed;
            }
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
