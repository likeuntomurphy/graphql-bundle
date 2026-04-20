<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\Manager;

use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Model\PageInfo;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Widget;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;

class FullWidgetManager implements GlobalObjectManagerInterface, ListableManagerInterface, CreatableManagerInterface, UpdatableManagerInterface, DeletableManagerInterface
{
    /** @var array<array-key, Widget> */
    private array $store = [];

    private int $nextId = 1;

    public static function getManagedGlobalObject(): string
    {
        return Widget::class;
    }

    public function seed(Widget ...$widgets): void
    {
        foreach ($widgets as $widget) {
            $this->store[$widget->getId()] = $widget;
        }
    }

    public function read(string $id): ?Widget
    {
        return $this->store[$id] ?? null;
    }

    public function create(object $document): object
    {
        \assert($document instanceof Widget);

        $document->id = (string) $this->nextId++;
        $this->store[$document->id] = $document;

        return $document;
    }

    public function update(object $document): object
    {
        \assert($document instanceof Widget);

        $this->store[$document->id] = $document;

        return $document;
    }

    public function delete(object $document): object
    {
        \assert($document instanceof Widget);

        unset($this->store[$document->id]);

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
