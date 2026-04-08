<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ValidationError extends ObjectType
{
    public function __construct(
    ) {
        parent::__construct([
            'name' => 'ValidationError',
            'description' => 'Represents a validation error for mutation input',
            'fields' => [
                'path' => [
                    'type' => Type::string(),
                    'description' => 'The input field path that caused the validation error.',
                ],
                'message' => [
                    'type' => Type::string(),
                    'description' => 'A human-readable description of the validation error.',
                ],
            ],
        ]);
    }
}
