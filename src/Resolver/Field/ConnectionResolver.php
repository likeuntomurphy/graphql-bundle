<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Model\Connection;
use Likeuntomurphy\GraphQL\Model\Edge;
use Likeuntomurphy\GraphQL\Model\PageInfo;

class ConnectionResolver
{
    /**
     * @param list<GlobalObjectInterface> $documents
     */
    public function resolve(array $documents, PageInfo $pageInfo): Connection
    {
        return new Connection(
            edges: array_map(
                fn (GlobalObjectInterface $doc) => new Edge(node: $doc, cursor: $doc->getId()),
                $documents,
            ),
            pageInfo: $pageInfo,
        );
    }
}
