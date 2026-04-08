<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Dto;

class ReadonlyDto
{
    public string $label;

    public function __construct(
        public readonly string $ref,
    ) {
    }
}
