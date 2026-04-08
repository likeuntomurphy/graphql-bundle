<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class WithResolver
{
    protected string $id;

    /** @phpstan-ignore argument.type */
    #[GraphQL\Resolver(resolver: 'App\Resolver\SecretResolver')]
    protected string $secret;
}
