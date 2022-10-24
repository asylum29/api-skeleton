<?php

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
class ParamType
{
    public $type;
}
