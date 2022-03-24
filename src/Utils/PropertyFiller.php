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
                        $obj->$methodName(
                            (bool) strtotime($value) && !is_numeric($value) ?
                                new DateTime($value) : $value
                        );
                    } elseif (is_array($params[$field])) {
                        $type = null;
                        $reader = new AnnotationReader();

                        /** @var ParamType $annotation */
                        $annotation = $reader->getMethodAnnotation($method, ParamType::class);
                        if (!empty($annotation)) {
                            $type = $annotation->type;
                        }
                        if (!empty($type)) {
                            $object = self::create($type, $params[$field]);
                            $obj->$methodName($object);
                            continue;
                        }

                        /** @var ParamArrayType $annotation */
                        $annotation = $reader->getMethodAnnotation($method, ParamArrayType::class);
                        if (!empty($annotation)) {
                            $type = $annotation->type;
                        }
                        if (!empty($type)) {
                            $array = [];
                            foreach ($params[$field] as $element) {
                                $array[] = self::create($type, $element);
                            }
                            $obj->$methodName($array);
                            continue;
                        }

                        $obj->$methodName($params[$field]);
                    }
                }
            }
        }

        return $obj;
    }
}
