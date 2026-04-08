<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class InvalidMutationFieldException extends \LogicException
{
    public function __construct(string $serviceId, \ReflectionException $previous)
    {
        parent::__construct(sprintf('Failed to build mutation fields for "%s": %s', $serviceId, $previous->getMessage()), previous: $previous);
    }
}
