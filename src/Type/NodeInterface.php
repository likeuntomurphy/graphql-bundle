<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Resolver\Type\ObjectTypeResolver;

class NodeInterface extends InterfaceType
{
    public const string TYPE_NAME = 'NodeInterface';

    public function __construct(
        ObjectTypeResolver $nodeResolver,
    ) {
        parent::__construct([
            'description' => 'Represents any node in the graph queryable by an ID.',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::id()),
                    'description' => 'The globally unique identifier for the node.',
                ],
            ],
            'resolveType' => $nodeResolver(...),
        ]);
    }
}
