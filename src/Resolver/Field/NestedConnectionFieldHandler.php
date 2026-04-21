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

        if (!\is_object($source)) {
            throw new \LogicException(\sprintf('Expected source to be an object, got %s.', get_debug_type($source)));
        }

        $result = ($this->connection)($source, new CursorPaginationParams($first, $after));

        return $this->resolver->resolve($result->results, $result->pageInfo);
    }
}
