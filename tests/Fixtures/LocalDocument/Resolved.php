<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Resolved
{
    /** @phpstan-ignore argument.type */
    #[GraphQL\Resolver(resolver: 'App\Resolver\CustomResolver')]
    protected string $computed;
}
