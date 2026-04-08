<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface ReadableManagerInterface
{
    public function read(string $id): ?object;
}
