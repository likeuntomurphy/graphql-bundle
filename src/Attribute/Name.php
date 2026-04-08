<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Name
{
    public function __construct(
        public string $name,
    ) {
    }
}
