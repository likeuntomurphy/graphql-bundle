<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Argument;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;

class First extends Argument
{
    /** @var array{name: 'first', type: callable(): ScalarType, description: string} */
    public const array CONFIG = [
        'name' => 'first',
        'type' => [Type::class, 'int'],
        'description' => 'The number of items to return from the beginning of the list.',
    ];

    public function __construct()
    {
        parent::__construct(self::CONFIG);
    }
}
