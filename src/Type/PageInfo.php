<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PageInfo extends ObjectType
{
    public const string TYPE_NAME = 'PageInfo';

    public function __construct()
    {
        parent::__construct([
            'description' => 'Pagination metadata for a connection.',
            'fields' => [
                'hasPreviousPage' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'Indicates whether the parent connection has a previous page.',
                ],
                'hasNextPage' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'Indicates whether the parent connection has a next page.',
                ],
                'startCursor' => [
                    'type' => Type::string(),
                    'description' => 'Provides a short-cut to the first cursor of the current page.',
                ],
                'endCursor' => [
                    'type' => Type::string(),
                    'description' => 'Provides a short-cut to the last cursor of the current page.',
                ],
                'totalCount' => [
                    'type' => Type::int(),
                    'description' => 'The total number of edges available through the connection.',
                ],
            ],
        ]);
    }
}
