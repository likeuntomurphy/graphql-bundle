<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\Attribute\AsConnection;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Attachment;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\ProjectWithAttachments;

class ConnectionFieldManager implements GlobalObjectManagerInterface, ListableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return ProjectWithAttachments::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    /** @return PaginatedResults<GlobalObjectInterface> */
    public function list(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }

    /** @return PaginatedResults<Attachment> */
    #[AsConnection('attachments')]
    public function findAttachments(ProjectWithAttachments $source, CursorPaginationParams $params): PaginatedResults
    {
        return new PaginatedResults([], new PageInfo(false, null, null));
    }
}
