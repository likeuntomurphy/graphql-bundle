<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class UnsupportedTypeException extends \LogicException
{
    public function __construct(\Stringable $type, string $property)
    {
        parent::__construct(sprintf('Unsupported type "%s" for property "%s".', $type, $property));
    }
}
