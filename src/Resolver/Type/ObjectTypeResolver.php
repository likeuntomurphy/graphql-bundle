<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\Exception\ObjectTypeResolutionException;
use Likeuntomurphy\GraphQL\Exception\UnknownTypeException;
use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ObjectTypeResolver
{
    /** @param array<class-string, string> $classMap */
    public function __construct(
        private TypeRegistry $typeRegistry,
        #[Autowire(param: 'likeuntomurphy_graphql.type_class_map')] private array $classMap = [],
    ) {
    }

    public function __invoke(mixed $source, mixed $context, ResolveInfo $info): ObjectType
    {
        if (!\is_object($source)) {
            throw new \RuntimeException(\sprintf('Expected an object source, got %s.', get_debug_type($source)));
        }

        $typeName = $this->classMap[$source::class]
            ?? throw new \RuntimeException(\sprintf('No type mapping for class %s.', $source::class));
        $type = $this->typeRegistry->get($typeName)
            ?? throw new UnknownTypeException($typeName);

        if (!$type instanceof ObjectType) {
            throw new ObjectTypeResolutionException($type);
        }

        return $type;
    }
}
