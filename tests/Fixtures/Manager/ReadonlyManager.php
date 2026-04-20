<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ReadonlyDocument;

class ReadonlyManager implements GlobalObjectManagerInterface, CreatableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return ReadonlyDocument::class;
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
