<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;

class InvalidManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return 'App\Document\NonExistent'; // @phpstan-ignore return.type
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
