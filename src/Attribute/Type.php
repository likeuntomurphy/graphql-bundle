<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class Type
{
    public function __construct(
        public string $name,
    ) {
    }
}
