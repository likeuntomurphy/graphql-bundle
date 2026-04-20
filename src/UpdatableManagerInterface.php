<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface UpdatableManagerInterface extends GlobalObjectManagerInterface
{
    public function update(object $document): object;
}
