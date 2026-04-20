<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Ticket;

class TicketManager implements GlobalObjectManagerInterface, CreatableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Ticket::class;
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
