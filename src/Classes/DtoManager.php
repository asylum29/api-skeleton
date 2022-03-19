<?php

namespace App\Classes;

use App\Annotation\DtoParamArrayType;
use App\Annotation\DtoParamType;
use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;

class DtoManager
{
    /**
     * Заполняет объект Dto полями из массива.
     *
     * @param string $class
     * @param array $fields
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public static function fill(string $class, array $fields)
    {
        $dto = new $class();

        $params = [];
        foreach ($fields as $key => $value) {
            $newKey = strtr(ucwords(strtr($key, ['_' => ' '])), [' ' => '']);
            $newKey = mb_strtolower(mb_substr($newKey, 0, 1)).mb_substr($newKey, 1);
            $params[$newKey] = $value;
        }

        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if ('set' == mb_substr($methodName, 0, 3)) {
                $field = mb_substr($methodName, 3);
                $field = mb_strtolower(mb_substr($field, 0, 1)).mb_substr($field, 1);
                if (isset($params[$field])) {
                    if (is_scalar($params[$field])) {
                        $value = trim($params[$field]);
                        $dto->$methodName((bool) strtotime($value) ? new DateTime($value) : $value);
                    } elseif (is_array($params[$field])) {
                        $type = null;
                        $reader = new AnnotationReader();

                        /** @var DtoParamType $annotation */
                        $annotation = $reader->getMethodAnnotation($method, DtoParamType::class);
                        if (!empty($annotation)) {
                            $type = $annotation->type;
                        }
                        if (!empty($type)) {
                            $object = self::fill($type, $params[$field]);
                            $dto->$methodName($object);
                            continue;
                        }

                        /** @var DtoParamArrayType $annotation */
                        $annotation = $reader->getMethodAnnotation($method, DtoParamArrayType::class);
                        if (!empty($annotation)) {
                            $type = $annotation->type;
                        }
                        if (!empty($type)) {
                            $array = [];
                            foreach ($params[$field] as $element) {
                                $array[] = self::fill($type, $element);
                            }
                            $dto->$methodName($array);
                            continue;
                        }

                        $dto->$methodName($params[$field]);
                    }
                }
            }
        }

        return $dto;
    }
}
