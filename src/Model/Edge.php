<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

readonly class Edge
{
    public function __construct(
        public mixed $node,
        public string $cursor,
    ) {
    }
}
