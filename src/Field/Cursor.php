<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

class Cursor extends FieldDefinition
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'cursor',
            'type' => Type::string(),
            'description' => 'A cursor for use in pagination.',
        ]);
    }
}
