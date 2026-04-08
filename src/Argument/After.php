<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Argument;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;

class After extends Argument
{
    /** @var array{name: 'after', type: callable(): ScalarType, description: string} */
    public const array CONFIG = [
        'name' => 'after',
        'type' => [Type::class, 'id'],
        'description' => 'The cursor after which to return items.',
    ];

    public function __construct()
    {
        parent::__construct(self::CONFIG);
    }
}
