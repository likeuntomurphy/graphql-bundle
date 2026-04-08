<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

readonly class PageInfo
{
    public function __construct(
        public bool $hasNextPage,
        public ?string $startCursor,
        public ?string $endCursor,
        public bool $hasPreviousPage = false,
    ) {
    }
}
