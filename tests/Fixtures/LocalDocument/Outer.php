<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Outer
{
    #[GraphQL\Description('The nested inner object')]
    protected Inner $inner;
}
