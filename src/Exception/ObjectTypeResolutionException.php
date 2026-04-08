<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

use GraphQL\Type\Definition\Type;

class ObjectTypeResolutionException extends \LogicException
{
    public function __construct(Type $type)
    {
        parent::__construct('Cannot resolve an object type to '.$type);
    }
}
