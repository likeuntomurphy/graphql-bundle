<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use App\Document\NonExistent;
use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;

class InvalidConnectionManager implements GlobalObjectManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return ProjectWithAttachments::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    /** @return PaginatedResults<NonExistent> @phpstan-ignore generics.notSubtype */
    #[AsConnection('items')]
    public function findItems(ProjectWithAttachments $source, CursorPaginationParams $params): PaginatedResults // @phpstan-ignore class.notFound
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }
}
