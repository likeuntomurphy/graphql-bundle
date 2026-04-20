<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

class ReadonlyDocument
{
    public string $label;

    public function __construct(
        public readonly string $ref = '',
    ) {
    }
}
