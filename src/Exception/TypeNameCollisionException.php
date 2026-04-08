<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Exception;

class TypeNameCollisionException extends \LogicException
{
    public function __construct(string $name, string $existingClass, string $newClass)
    {
        parent::__construct(sprintf(
            'Type name "%s" resolved by "%s" is already registered by "%s". Apply the #[Likeuntomurphy\GraphQL\Attribute\Name] attribute to one of these classes to resolve the collision.',
            $name,
            $newClass,
            $existingClass,
        ));
    }
}
