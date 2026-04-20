<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Collision\Project;

class CollidingProjectManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Project::class;
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
