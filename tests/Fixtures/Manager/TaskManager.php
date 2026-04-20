<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Task;

class TaskManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Task::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
