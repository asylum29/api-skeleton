<?php

namespace App\Utils;

use App\Annotation\ParamArrayType;
use App\Annotation\ParamType;
use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;

class PropertyFiller
{
    /**
     * Создает объект и заполняет его поля значениями из массива.
     *
     * @param string $class
     * @param array $fields
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public static function create(string $class, array $fields)
    {
        $obj = new $class();

        $params = [];
        foreach ($fields as $key => $value) {
            $newKey = strtr(ucwords(strtr($key, ['_' => ' '])), [' ' => '']);
            $newKey = mb_strtolower(mb_substr($newKey, 0, 1)).mb_substr($newKey, 1);
            $params[$newKey] = $value;
        }

        $reader = new AnnotationReader();
        $reflection = new ReflectionClass($class);

        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (0 !== mb_strpos($methodName, 'set')) {
                continue;
            }

            $field = mb_substr($methodName, 3);
            $field = mb_strtolower(mb_substr($field, 0, 1)).mb_substr($field, 1);
            if (!isset($params[$field])) {
                continue;
            }

            if (is_scalar($params[$field])) {
                $value = trim($params[$field]);
                $obj->$methodName(
                    strtotime($value) && !is_numeric($value) ? new DateTime($value) : $value
                );
                unset($params[$field]);
            } elseif (is_array($params[$field])) {
                $type = null;

                /** @var ParamType $annotation */
                $annotation = $reader->getMethodAnnotation($method, ParamType::class);
                if (null !== $annotation) {
                    $type = $annotation->type;
                }
                if (!empty($type)) {
                    $object = self::create($type, $params[$field]);
                    $obj->$methodName($object);
                    unset($params[$field]);
                    continue;
                }

                /** @var ParamArrayType $annotation */
                $annotation = $reader->getMethodAnnotation($method, ParamArrayType::class);
                if (null !== $annotation) {
                    $type = $annotation->type;
                }
                if (!empty($type)) {
                    $array = [];
                    foreach ($params[$field] as $element) {
                        $array[] = self::create($type, $element);
                    }
                    $obj->$methodName($array);
                    unset($params[$field]);
                    continue;
                }

                $obj->$methodName($params[$field]);
            }
        }

        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $field = $property->getName();
            if (!isset($params[$field])) {
                continue;
            }

            if (is_scalar($params[$field])) {
                $value = trim($params[$field]);
                $obj->$field = strtotime($value) && !is_numeric($value) ? new DateTime($value) : $value;
            } elseif (is_array($params[$field])) {
                $type = null;

                /** @var ParamType $annotation */
                $annotation = $reader->getPropertyAnnotation($property, ParamType::class);
                if (null !== $annotation) {
                    $type = $annotation->type;
                }
                if (!empty($type)) {
                    $object = self::create($type, $params[$field]);
                    $obj->$field = $object;
                    continue;
                }

                /** @var ParamArrayType $annotation */
                $annotation = $reader->getPropertyAnnotation($property, ParamArrayType::class);
                if (null !== $annotation) {
                    $type = $annotation->type;
                }
                if (!empty($type)) {
                    $array = [];
                    foreach ($params[$field] as $element) {
                        $array[] = self::create($type, $element);
                    }
                    $obj->$field = $array;
                    continue;
                }

                $obj->$field = $params[$field];
            }
        }

        return $obj;
    }
}
