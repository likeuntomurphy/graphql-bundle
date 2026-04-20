<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Dto\ProjectDto;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;

class DeleteOnlyManager implements GlobalObjectManagerInterface, DeletableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Project::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return ProjectDto::class;
    }

    public function read(string $id): ?object
    {
        return new \stdClass();
    }

    public function delete(object $document): object
    {
        return new \stdClass();
    }
}
