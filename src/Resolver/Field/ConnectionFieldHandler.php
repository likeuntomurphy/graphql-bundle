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

        $result = $this->manager->list($params, null);

        return $this->resolver->resolve($result->results, $result->pageInfo);
    }
}
