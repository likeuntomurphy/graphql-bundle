<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Dto\UnsupportedDto;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Project;

class UnsupportedManager implements GlobalObjectManagerInterface, CreatableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Project::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return UnsupportedDto::class;
    }

    public function create(object $dto, object $document, array $validationGroups = []): object
    {
        return new \stdClass();
    }
}
