<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(
        public string $description,
    ) {
    }
}
