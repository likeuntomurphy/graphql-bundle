<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class GlobalObject
{
    public const string RESOURCE_TAG = 'graphql.global_object';

    /** @param class-string $manager */
    public function __construct(
        public readonly string $manager,
    ) {
    }
}
