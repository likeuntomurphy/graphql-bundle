<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;

class NonGenericConnectionManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return ProjectWithAttachments::class;
    }

    #[AsConnection('items')] // @phpstan-ignore missingType.generics
    public function findItems(ProjectWithAttachments $source, CursorPaginationParams $params): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }

    public function read(string $id): ?object
    {
        return null;
    }
}
