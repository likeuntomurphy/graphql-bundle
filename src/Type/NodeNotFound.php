<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class NodeNotFound extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'NodeNotFound',
            'description' => 'Indicates that the requested node was not found.',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::id()),
                    'description' => 'The node identifier that could not be resolved.',
                ],
            ],
        ]);
    }
}
