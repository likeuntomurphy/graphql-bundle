<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Dto\NullableDto;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;

class NullableManager implements GlobalObjectManagerInterface, CreatableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Project::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return NullableDto::class;
    }

    public function create(object $dto, object $document, array $validationGroups = []): object
    {
        return new \stdClass();
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
