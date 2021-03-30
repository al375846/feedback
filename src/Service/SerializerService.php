<?php

namespace App\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerService {


    /**
     * @var Serializer
     */
    private Serializer $serializer;

    public function __construct()
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory, null, null, new ReflectionExtractor())
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }
}
