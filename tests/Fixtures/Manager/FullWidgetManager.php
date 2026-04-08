<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\ReadableManagerInterface;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Widget;

class FullWidgetManager implements GlobalObjectManagerInterface, ReadableManagerInterface, ListableManagerInterface, CreatableManagerInterface
{
    /** @var array<string, Widget> */
    private array $store = [];

    public static function getManagedGlobalObject(): string
    {
        return Widget::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return \stdClass::class;
    }

    public function seed(Widget ...$widgets): void
    {
        foreach ($widgets as $widget) {
            $this->store[$widget->getId()] = $widget;
        }
    }

    public function read(string $id): ?object
    {
        return $this->store[$id] ?? null;
    }

    public function create(object $dto, object $document, array $validationGroups = []): object
    {
        return $document;
    }

    /** @return PaginatedResults<Widget> */
    public function list(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults
    {
        $results = array_values($this->store);

        $first = reset($results);
        $last = end($results);

        return new PaginatedResults($results, new PageInfo(
            hasNextPage: false,
            startCursor: false !== $first ? $first->getId() : null,
            endCursor: false !== $last ? $last->getId() : null,
        ));
    }
}
