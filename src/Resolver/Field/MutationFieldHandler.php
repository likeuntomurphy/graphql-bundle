<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;

class MutationFieldHandler
{
    /** @param list<string> $idFields */
    public function __construct(
        private string $method,
        private string $typeName,
        private MutationFieldResolver $resolver,
        private array $idFields = [],
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
        return $this->resolver->resolve($this->method, $this->typeName, $args, $this->idFields);
    }
}
