<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
class Resolver
{
    /** @param class-string $resolver */
    public function __construct(
        public string $resolver,
    ) {
    }
}
