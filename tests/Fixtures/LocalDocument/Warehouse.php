<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Warehouse
{
    protected string $street;

    #[GraphQL\Exclude]
    protected string $internal;
}
