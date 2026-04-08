<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Venue
{
    #[GraphQL\Description('The street address')]
    protected string $street;
}
