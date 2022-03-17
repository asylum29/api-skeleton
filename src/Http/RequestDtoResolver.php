<?php

namespace App\Http;

use App\Annotation\Dto;
use App\Classes\DtoManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Generator;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RequestDtoResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $class = $argument->getType();
        if (class_exists($class)) {
            $reader = new AnnotationReader();
            $reflection = new ReflectionClass($class);
            $annotations = $reader->getClassAnnotations($reflection);
            $annotations = array_map(function ($annotation) {
                return get_class($annotation);
            }, $annotations);

            return in_array(Dto::class, $annotations);
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        yield DtoManager::fill($argument->getType(), array_merge(
            $request->request->all(),
            $request->query->all(),
            $request->attributes->all()
        ));
    }
}
