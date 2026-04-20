<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

interface DeletableManagerInterface extends GlobalObjectManagerInterface
{
    public function delete(object $document): object;
}
