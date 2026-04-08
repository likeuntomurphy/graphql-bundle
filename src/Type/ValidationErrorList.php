<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\TypeRegistry;

class ValidationErrorList extends ObjectType
{
    public function __construct(
        private TypeRegistry $typeRegistry,
    ) {
        parent::__construct([
            'name' => 'ValidationErrorList',
            'description' => 'Holds a list of validation errors for mutation input',
            'fields' => [
                'errors' => [
                    'type' => Type::listOf($this->typeRegistry->get('ValidationError')), // @phpstan-ignore argument.type
                    'description' => 'The list of validation errors.',
                ],
            ],
        ]);
    }
}
