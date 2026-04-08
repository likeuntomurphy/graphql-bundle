<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

readonly class NodeNotFound
{
    public function __construct(
        public string $id,
    ) {
    }
}
