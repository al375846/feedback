<?php

namespace App\Controller;

use App\Entity\Apprentice;
use App\Entity\Category;
use App\Service\UploaderService;
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
use App\Entity\Publication;

class PublicationController extends AbstractController
{
    #[Route('/api/publication', name: 'publication_post', methods: ['POST'])]
    /**
     * @OA\Response(response=200, description="Adds a publication",
     *     @OA\JsonContent(type="object",
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="category", type="object", @OA\Property(property="name", type="string")),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="video", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="apprentice", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *     @OA\Property(property="date", type="string", format="date-time")
     * ))
     * @OA\RequestBody(description="Input data format",
     *     @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *     @OA\Property(property="video", type="string", format="binary"),
     *     @OA\Property(property="document", type="string", format="binary"),
     *     @OA\Property(property="image", type="string", format="binary"),
     *     @OA\Property(property="publication", type="object",
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="category", type="object", @OA\Property(property="name", type="string")),
     *          @OA\Property(property="description", type="string"),
     *          @OA\Property(property="tags", type="array", @OA\Items(type="string")),
     *          @OA\Property(property="apprentice", type="object", @OA\Property(property="userdata", type="object", @OA\Property(property="username", type="string"))),
     *          @OA\Property(property="date", type="string", format="date-time"))
     *     )
     *     )
     * )
     * @OA\Tag(name="Publications")
     * @Security(name="Bearer")
     */
    public function postPublication(Request $request, UploaderService $uploaderService): Response
    {
        //dump($request->files);
        //Inicialiazamos los normalizadores y los codificadores para serialiar y deserializar
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())];
        $serializer = new Serializer($normalizers, $encoders);

        //Deserializamos para obtener los datos del objeto
        $publication = $serializer->deserialize($request->request->get("publication"), Publication::class, 'json');

        //Trabajamos los datos como queramos
        $em = $this->getDoctrine()->getManager();
        //Decidimos la categoria
        $category = $this->getDoctrine()->getRepository(Category::class)->findBy(['name'=>$publication->getCategory()->getName()], null);
        $publication->setCategory($category[0]);
        //Decidimos el aprendiz
        $userdata = $this->getDoctrine()->getRepository(User::class)->findBy(['username'=>$publication->getApprentice()->getUserdata()->getUsername()], null);
        $apprentice = $this->getDoctrine()->getRepository(Apprentice::class)->findBy(['userdata'=>$userdata], null);
        $publication->setApprentice($apprentice[0]);
        //Subimos los archivos
        $images = array();
        $document = null;
        $video = null;
        foreach($request->files->getIterator() as $file) {
            $filename = $uploaderService->upload($file);
            $arrayfile = explode(".", $filename);
            $extension = $arrayfile[count($arrayfile) - 1];
            if ($extension == "pdf") {
                $document = $filename;

            }
            else {
                if ($extension == "mp4") {
                    $video = $filename;
                }
                elseif ($extension == "jpg" or $extension == "jpeg" or $extension == "png") {
                    $images[count($images)] = $filename;
                }
            }
        }
        $publication->setVideo($video);
        $publication->setDocument($document);
        $publication->setImages($images);
        $em->persist($publication);
        $em->flush();

        //Serializamos para poder mandar el objeto en la respuesta
        $data = $serializer->serialize($publication, 'json', [AbstractNormalizer::GROUPS => ['publications'], AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']]);

        //Puede tener los atributos que se quieran
        $response=array(
            'status'=>200,
            'publication'=>json_decode($data)
        );

        return new JsonResponse($response,200);
    }
}
