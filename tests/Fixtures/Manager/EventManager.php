<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;

class EventManager implements GlobalObjectManagerInterface
{
    public function read(string $id): ?object
    {
        return null;
    }
}
