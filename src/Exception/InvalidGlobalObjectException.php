<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class InvalidGlobalObjectException extends \LogicException
{
    public function __construct(string $serviceId, \ReflectionException $previous)
    {
        parent::__construct(sprintf('Failed to build object type for "%s": %s', $serviceId, $previous->getMessage()), previous: $previous);
    }
}
