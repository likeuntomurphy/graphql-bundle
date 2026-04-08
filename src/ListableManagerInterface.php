<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;

interface ListableManagerInterface
{
    /** @return PaginatedResults<GlobalObjectInterface> */
    public function list(CursorPaginationParams $params, ?callable $filter): PaginatedResults;
}
