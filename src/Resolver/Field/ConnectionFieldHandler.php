<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;

class ConnectionFieldHandler
{
    public function __construct(
        private ListableManagerInterface $manager,
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

        $result = $this->manager->list(new CursorPaginationParams($first, $after), null);

        return $this->resolver->resolve($result->results, $result->pageInfo);
    }
}
