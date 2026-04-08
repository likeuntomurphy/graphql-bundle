<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

readonly class Connection
{
    /**
     * @param array<Edge> $edges
     */
    public function __construct(
        public array $edges,
        public PageInfo $pageInfo,
    ) {
    }
}
