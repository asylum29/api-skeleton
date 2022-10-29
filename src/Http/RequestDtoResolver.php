<?php

namespace App\Http;

use App\Annotation\RequestDto;
use Doctrine\Common\Annotations\AnnotationReader;
use Generator;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;

class RequestDtoResolver implements ArgumentValueResolverInterface
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $class = $argument->getType();
        if (!class_exists($class)) {
            return false;
        }

        $reader = new AnnotationReader();
        $reflection = new ReflectionClass($class);
        $annotations = $reader->getClassAnnotations($reflection);
        $annotations = array_map(static function ($annotation) {
            return get_class($annotation);
        }, $annotations);

        return in_array(RequestDto::class, $annotations, true);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $contentData = [];
        if (!empty($content = $request->getContent())) {
            $contentData = json_decode($content, true);
        }
        $data = json_encode(array_merge(
            $request->request->all(),
            $request->query->all(),
            $request->attributes->all(),
            $contentData
        ));

        yield $this->serializer->deserialize($data, $argument->getType(), 'json');
    }
}
