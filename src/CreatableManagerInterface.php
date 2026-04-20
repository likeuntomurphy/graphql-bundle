<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface CreatableManagerInterface extends GlobalObjectManagerInterface
{
    public function create(object $document): object;
}
