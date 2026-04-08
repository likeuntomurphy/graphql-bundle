<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
readonly class AsConnection
{
    public function __construct(
        public string $fieldName,
    ) {
    }
}
