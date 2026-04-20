<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Enum\ProjectValidationGroup;
use Likeuntomurphy\GraphQL\ValidatableManagerInterface;

class ValidatableCreateManager implements GlobalObjectManagerInterface, CreatableManagerInterface, ValidatableManagerInterface
{
    public static function getValidationGroupEnum(): string
    {
        return ProjectValidationGroup::class;
    }

    public function create(object $document): object
    {
        return new \stdClass();
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
