<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TypeRegistry
{
    public const string TAG = 'graphql.type';

    public const string NON_NULL_SUFFIX = 'non_null';
    public const string LIST_OF_SUFFIX = 'list_of';

    public const string STRING = self::TAG.'.String';
    public const string INT = self::TAG.'.Int';
    public const string FLOAT = self::TAG.'.Float';
    public const string BOOLEAN = self::TAG.'.Boolean';
    public const string ID = self::TAG.'.ID';

    /** @param ServiceLocator<((EnumType|InterfaceType|ObjectType|ScalarType|UnionType)&OutputType)|InputObjectType> $types */
    public function __construct(
        #[AutowireLocator(self::TAG, indexAttribute: 'name')] private ServiceLocator $types,
    ) {
    }

    /** @return null|((EnumType|InterfaceType|ObjectType|ScalarType|UnionType)&OutputType)|InputObjectType */
    public function get(string $id): EnumType|InputObjectType|InterfaceType|ObjectType|ScalarType|UnionType|null
    {
        if (!$this->types->has($id)) {
            return null;
        }

        return $this->types->get($id);
    }

    public function has(string $id): bool
    {
        return $this->types->has($id);
    }
}
