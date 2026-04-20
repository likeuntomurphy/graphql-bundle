<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;

class CrudManager implements GlobalObjectManagerInterface, CreatableManagerInterface, UpdatableManagerInterface, DeletableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Project::class;
    }

    public function read(string $id): ?object
    {
        return new \stdClass();
    }

    public function create(object $document): object
    {
        return new \stdClass();
    }

    public function update(object $document): object
    {
        return new \stdClass();
    }

    public function delete(object $document): object
    {
        return new \stdClass();
    }
}
