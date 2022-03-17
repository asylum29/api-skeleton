<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class DtoParamArrayType
{
    public $type;
}
