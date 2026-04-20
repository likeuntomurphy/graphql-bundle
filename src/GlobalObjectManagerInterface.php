<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface GlobalObjectManagerInterface
{
    public const string TAG = 'graphql.global_object_manager';

    public function read(string $id): ?object;
}
