<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;

interface DefaultFieldResolverInterface
{
    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $context
     */
    public function __invoke(
        mixed $source,
        array $args,
        array $context,
        ResolveInfo $info,
    ): mixed;
}
