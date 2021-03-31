<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Category;
use App\Entity\Feedback;
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

class PublicationController extends AbstractController
{
    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializer = $serializerService->getSerializer();
    }

    #[Route('/api/publication', name: 'publication_post', methods: ['POST'])]
    /**
     * @Route("/api/publication", name="publication_post", methods={"POST"})
     * @OA\Response(response=200, description="Adds a publication",
     *     @OA\JsonContent(type="object",
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
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function postPublication(Request $request): Response
    {
        //Deserialize to obtain object data
        $publication = $this->serializer->deserialize($request->getContent(), Publication::class, 'json');

        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get category
        $catName = $publication->getCategory()->getName();
        $category = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$catName]);
        if ($category == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }
        $publication->setCategory($category);

        //Get apprentice
        $user = $this->getUser();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['userdata'=>$user]);
        $publication->setApprentice($apprentice);

        //Save publication
        $em->persist($publication);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($publication, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/publication', name: 'publication_get', methods: ['GET'])]
    /**
     * @Route("/api/publication", name="publication_get", methods={"GET"})
     * @OA\Parameter(name="cursor", in="query", required=false)
     * @OA\Parameter(name="filter", in="query", required=false)
     * @OA\Response(response=200, description="Gets all publications",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publications", type="array", @OA\Items(
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="document", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param Request $request
     * @return Response
     */
    public function getPublications(Request $request): Response
    {
        //Get cursor and filter
        $cursor = $request->query->get('cursor', -1);
        $filter = strtolower($request->query->get('filter', ""));

        //Get publications
        $itemSize = 25;
        if ($cursor == -1) {
            $dql = "SELECT p
                    FROM App\Entity\Publication p
                    JOIN p.category c
                    JOIN p.apprentice a
                    WHERE LOWER(c.name) LIKE :filter
                    OR LOWER(p.title) LIKE :filter
                    OR LOWER(a.username) LIKE :filter
                    OR LOWER(p.tags) LIKE :filter
                    ORDER BY p.id DESC";
            $query = $this->getDoctrine()->getManager()->createQuery($dql)
                ->setParameter('filter', '%'.$filter.'%')
                ->setFirstResult(0)
                ->setMaxResults($itemSize);
        }
        else {
            $dql = "SELECT p 
                    FROM App\Entity\Publication p 
                    JOIN p.category c
                    JOIN p.apprentice a
                    WHERE p.id < :cursor AND
                    (LOWER(c.name) LIKE :filter
                    OR LOWER(p.title) LIKE :filter
                    OR LOWER(a.username) LIKE :filter
                    OR LOWER(p.tags) LIKE :filter)
                    ORDER BY p.id DESC";
            $query = $this->getDoctrine()->getManager()->createQuery($dql)
                ->setParameter('filter', '%'.$filter.'%')
                ->setParameter('cursor', $cursor)
                ->setFirstResult(0)
                ->setMaxResults($itemSize);
        }

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $publications = [];
        foreach ($paginator as $publication) {
            $publications[] = $publication;
        }

        //Serialize the response data
        $data = $this->serializer->serialize($publications, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publications'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/publication/{id}', name: 'publication_get_id', methods: ['GET'])]
    /**
     * @Route("/api/publication/{id}", name="publication_get_id", methods={"GET"})
     * @OA\Response(response=200, description="Gets a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="publication", type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object",
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getPublication($id): Response
    {
        //Get publication
        $publication = $this->getDoctrine()->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }

        //Serialize the response data
        $data = $this->serializer->serialize($publication, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response, 200);
    }

    #[Route('/api/publication/{id}/feedback', name: 'feedback_publication_get', methods: ['GET'])]
    /**
     * @Route("/api/publication/{id}/feedback", name="feedback_publication_get", methods={"GET"})
     * @OA\Response(response=200, description="Gets all feedbacks from a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="feedbacks", type="array", @OA\Items(type="object",
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
     * ))))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function getPublicationFeedback($id): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();

        //Get publication
        $publication = $doctrine->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }
        $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['publication' => $publication]);

        //Serialize the response data
        $data = $this->serializer->serialize($feedbacks, 'json', [
            AbstractNormalizer::GROUPS => ['feedbacks'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['publication']
        ]);

        //Create the response
        $response=array('feedbacks'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/publication/{id}', name: 'publication_put', methods: ['PUT'])]
    /**
     * @Route("/api/publication/{id}", name="publication_put", methods={"PUT"})
     * @OA\Response(response=200, description="Edits a publication",
     *     @OA\JsonContent(type="object",
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
     *          @OA\Property(property="username", type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * )))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object",
     *          @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function putPublication($id, Request $request): Response
    {
        //Get doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get old publication
        $old = $doctrine->getRepository(Publication::class)->find($id);
        if ($old == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }

        //Deserialize to obtain object data
        $this->serializer->deserialize($request->getContent(), Publication::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $old
        ]);

        //Get category
        $catName = $old->getCategory()->getName();
        $category = $doctrine->getRepository(Category::class)->findOneBy(['name'=>$catName]);
        if ($category == null) {
            $response=array('error'=>'Category not found');
            return new JsonResponse($response,404);
        }
        $old->setCategory($category);

        //Get apprentice
        $user = $this->getUser();
        $apprentice = $doctrine->getRepository(Apprentice::class)->findOneBy(['userdata'=>$user]);
        $old->setApprentice($apprentice);

        //Save publication
        $em->persist($old);
        $em->flush();

        //Serialize the response data
        $data = $this->serializer->serialize($old, 'json', [
            AbstractNormalizer::GROUPS => ['publications']
        ]);

        //Create the response
        $response=array('publication'=>json_decode($data));

        return new JsonResponse($response,200);
    }

    #[Route('/api/publication/{id}', name: 'publication_delete', methods: ['DELETE'])]
    /**
     * @Route("/api/publication/{id}", name="publication_delete", methods={"DELETE"})
     * @OA\Response(response=200, description="Deletes a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="deleted", type="boolean")
     * ))
     * @OA\Response(response=404, description="Not found",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="error", type="string")
     * ))
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     * @param $id
     * @return Response
     */
    public function deletePublication($id): Response
    {
        //Get the doctrine
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        //Get the publication
        $publication = $this->getDoctrine()->getRepository(Publication::class)->find($id);
        if ($publication == null) {
            $response=array('error'=>'Publication not found');
            return new JsonResponse($response,404);
        }

        //Get the apprentice
        $apprentice = $publication->getApprentice();
        if ($apprentice != null) {
            $apprentice->removePublication($publication);
            $em->persist($apprentice);
        }

        //Remove the publication
        $em->remove($publication);
        $em->flush();

        //Create the response
        $response=array('deleted'=>true);

        return new JsonResponse($response,200);
    }
}
