<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Event;

class EventManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Event::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }
}
