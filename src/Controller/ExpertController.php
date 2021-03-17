<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Expert;
use App\Entity\ExpertCategories;
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
class ExpertController extends AbstractController
{
    #[Route('/api/expert/category/{id}', name: 'expert', methods: ['POST'])]
    /**
     * @OA\Response(response=200, description="Adds a feedback",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="exepert", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="categories", type="array", @OA\Items(type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="name", type="string")))
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="name", type="string")
     * ))
     * @OA\Tag(name="Experts")
     * @Security(name="Bearer")
     */
    public function postFavCategory($id, Request $request): Response
    {
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['username']]);
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json', [AbstractNormalizer::ATTRIBUTES => ['name']]);

        //Trabajamos los datos como queramos
        $em = $this->getDoctrine()->getManager();
        //Obtenemos la categoria
        $cat = $this->getDoctrine()->getRepository(Category::class)->find($id);
        //Obetenemos el experto
        $userdata = $this->getDoctrine()->getRepository(User::class)->findBy(['username'=>$user->getUsername()]);
        $expert = $this->getDoctrine()->getRepository(Expert::class)->findBy(['userdata'=>$userdata[0]])[0];
        //Añadimos la categoria
        $favCat = new ExpertCategories();
        $favCat->setCategory($cat);
        $favCat->setExpert($expert);
        $expert->addFavCategory($favCat);
        $em->persist($favCat);
        $em->persist($expert);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($expert, 'json', [AbstractNormalizer::GROUPS => ['fav_categories']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'favCategories'=>json_decode($data),
        );

        return new JsonResponse($response,200);
    }
}
