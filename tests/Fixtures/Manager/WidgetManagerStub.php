<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;

/**
 * Concrete stub so PHPUnit can mock instance methods while static methods remain callable.
 */
class WidgetManagerStub implements GlobalObjectManagerInterface, CreatableManagerInterface, UpdatableManagerInterface, DeletableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return \stdClass::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    public function read(string $id): ?object
    {
        return new \stdClass();
    }

    public function create(object $dto, object $object, array $validationGroups = []): object
    {
        return new \stdClass();
    }

    public function update(object $dto, object $document, array $validationGroups = []): object
    {
        return new \stdClass();
    }

    public function delete(object $document): object
    {
        return new \stdClass();
    }
}
