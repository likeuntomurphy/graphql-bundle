<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;

class MutationFieldHandler
{
    /**
     * @param class-string                                           $entityClass
     * @param array<string, array{property: string, target: string}> $relations   keyed by GraphQL arg name
     */
    public function __construct(
        private string $method,
        private string $typeName,
        private string $entityClass,
        private MutationFieldResolver $resolver,
        private array $relations = [],
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $context
     */
    public function __invoke(
        mixed $source,
        array $args,
        array $context,
        ResolveInfo $info,
    ): object {
        return $this->resolver->resolve($this->method, $this->typeName, $this->entityClass, $args, $this->relations);
    }
}
