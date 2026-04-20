<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface GlobalObjectManagerInterface
{
    public const string TAG = 'graphql.global_object_manager';

    /** @return class-string */
    public static function getManagedGlobalObject(): string;

    /** @return class-string */
    public static function getManagedDataTransferObject(): string;

    public function read(string $id): ?object;
}
