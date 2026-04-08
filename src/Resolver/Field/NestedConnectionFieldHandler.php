<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Likeuntomurphy\GraphQL\Pagination\PaginatedResults;

class NestedConnectionFieldHandler
{
    /**
     * @param \Closure(object, CursorPaginationParams): PaginatedResults<GlobalObjectInterface> $connection
     */
    public function __construct(
        private \Closure $connection,
        private ConnectionResolver $resolver,
        private int $limit,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $context
     */
    public function __invoke(
        mixed $source,
        array $args,
        array $context,
        ResolveInfo $info,
    ): Connection {
        /** @var null|int $first */
        $first = $args['first'] ?? null;

        /** @var null|string $after */
        $after = $args['after'] ?? null;

        $params = new CursorPaginationParams($this->limit)
            ->setFirst($first)
            ->setAfter($after)
        ;

        \assert(\is_object($source));
        $result = ($this->connection)($source, $params);

        return $this->resolver->resolve($result->results, $result->pageInfo);
    }
}
