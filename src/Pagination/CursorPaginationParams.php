<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Pagination;

readonly class CursorPaginationParams
{
    public function __construct(
        public ?int $first = null,
        public ?string $after = null,
    ) {
    }
}
