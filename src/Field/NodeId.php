<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Resolver\Field\NodeIdResolver;

class NodeId extends FieldDefinition
{
    public function __construct(
        NodeIdResolver $resolver,
    ) {
        parent::__construct([
            'name' => 'id',
            'type' => Type::NonNull(Type::id()),
            'description' => 'The globally unique identifier for this node.',
            'resolve' => $resolver(...),
        ]);
    }
}
