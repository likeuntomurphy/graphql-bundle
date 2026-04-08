<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Report;

class NonListableManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Report::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }
}
