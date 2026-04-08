<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;

class ScalarGenericConnectionManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return ProjectWithAttachments::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    /** @return PaginatedResults<int> @phpstan-ignore generics.notSubtype */
    #[AsConnection('items')]
    public function findItems(ProjectWithAttachments $source, CursorPaginationParams $params): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }
}
