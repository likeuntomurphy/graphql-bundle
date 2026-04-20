<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\UnsupportedDocument;

class UnsupportedManager implements GlobalObjectManagerInterface, CreatableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return UnsupportedDocument::class;
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
