<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Query\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeResolver;
use Likeuntomurphy\GraphQL\Type\NodeInterface;

class Node extends FieldDefinition implements FieldInterface
{
    public function __construct(
        NodeInterface $nodeInterface,
        NodeResolver $resolver,
    ) {
        parent::__construct([
            'name' => 'node',
            'type' => $nodeInterface,
            'description' => 'Fetch any node by its global ID.',
            'args' => [
                'id' => [
                    'type' => Type::NonNull(Type::id()),
                    'description' => 'The global ID of the node to fetch.',
                ],
            ],
            'resolve' => $resolver,
        ]);
    }
}
