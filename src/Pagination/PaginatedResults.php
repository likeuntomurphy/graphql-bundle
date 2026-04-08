<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Pagination;

use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;

/**
 * @template-covariant T of GlobalObjectInterface
 */
readonly class PaginatedResults
{
    /**
     * @param list<T> $results
     */
    public function __construct(
        public array $results,
        public PageInfo $pageInfo,
    ) {
    }
}
