<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class UnknownMutationMethodException extends \LogicException
{
    public function __construct(string $method)
    {
        parent::__construct(sprintf('Unknown mutation method "%s".', $method));
    }
}
